<?php


class Order
{
    private $_id;
    private $_status;
    private $_items;

    public $date;
    public $discount;

    public function __construct($id, $status, $date, $discount)
    {
        $this->_id = $id;
        $this->_status = $status;
        $this->date = $date;
        $this->discount = $discount;

        $this->_items = new ItemOrderCollection();
    }

    public function __get($property)
    {
        switch ($property)
        {
            case '_id':
                return $this->_id;
            case '_status':
                return $this->_status;
            case '_items':
                return $this->_items;
        }
    }

    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'id':
                $this->_id = $value;
                break;
            case 'status':
                $this->_status = $value;
                break;
        }
    }

    public function addItem($item)
    {
        if ($item instanceof ItemOrder) {
            $this->_items->addItem($item);
        }
    }

    public function removeItem($key)
    {
        $this->_items->removeItem($key);
    }

    public function getAmount()
    {
        $amount = 0;
        $keys = $this->_items->keys();
        foreach ($keys as $key) {
            $item = $this->_items->getItem($key);
            $amount += $item->qty * $item->price;
        }
        if (!empty($this->discount)) {
            return $amount*(100-$this->discount);
        } else {
            return $amount;
        }
    }
}

class ItemOrder {
    public $id;
    public $price;
    public $qty;
    public $title;

    public function __construct($id, $price, $qty, $title)
    {
        $this->id = $id;
        $this->price = $price;
        $this->qty = $qty;
        $this->title = $title;
    }
}

class Collection {
    private $_members = array();

    public function addItem($obj, $key = null)
    {
        if ($key) {
            if (isset($this->_members[$key])) {
                throw new KeyInUseException("Key \"$key\" already in use!");
            } else {
                $this->_members[$key] = $obj;
            }
        } else {
            $this->_members[] = $obj;
        }
    }

    public function removeItem($key)
    {
        if (isset($this->_members[$key])) {
            unset($this->_members[$key]);
        } else {
            throw new KeyInvalidException("Invalid key \"$key\"!");
        }
    }

    public function getItem($key)
    {
        if (isset($this->_members[$key])) {
            return $this->_members[$key];
        } else {
            throw new KeyInvalidException("Invalid key \"$key\"!");
        }
    }

    public function keys()
    {
        return array_keys($this->_members);
    }

    public function exists($key)
    {
        return (isset($this->_members[$key]));
    }

    public function length()
    {
        return sizeof($this->_members);
    }
}

class ItemOrderCollection extends Collection
{
    public function addItem(ItemOrder $obj, $key = null)
    {
        parent::addItem($obj, $obj->id);
    }
}

/**
 * Подключение к БД
 */
$mysqli = new mysqli('localhost', 'user', 'password', 'order');

/* Проверка соединения */
if (mysqli_connect_errno()) {
    printf("Подключение невозможно: %s\n", mysqli_connect_error());
    exit();
} else {
    $orders = array();
    $result = $mysqli->query("SELECT id, status, date, discount FROM orders WHERE status = 'Оплачено' AND date > '2020-01-01'");
    while ($row = $result->fetch_assoc()) {
        $order = new Order($row['id'], $row['status'], $row['date'], $row['discount']);

        $resultItem = $mysqli->query("SELECT id, price, qty, title FROM orders_items WHERE order_id = " . $row['id']);
        while ($rowItem = $resultItem->fetch_assoc()) {
            $order->addItem(new ItemOrder($rowItem['id'], $rowItem['price'], $rowItem['qty'], $rowItem['title']);
        }

        $orders[] = $order;
    }

    /* Закрыть подключение */
    $mysqli->close();
}

