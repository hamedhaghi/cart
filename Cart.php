<?php

namespace Tools;
/**
 * Class Cart
 * @package Tools
 */
class Cart
{
    /**
     * @var string
     */
    private $session_name = 'cart';
    /**
     * @var bool
     */
    private $increment_qty = true;
    /**
     * @var int
     */
    public $max_items = 10;
    /**
     * @var int
     */
    public $key;
    /**
     * @var null
     */
    public $id;
    /**
     * @var int
     */
    public $qty;
    /**
     * @var null
     */
    public $options;

    /**
     * Cart constructor.
     * @param null $id
     * @param int $qty
     * @param null $options
     */
    public function __construct($id = null, $qty = 1, $options = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->key = $this->KeyGenerator();
        $this->id = $id;
        $this->qty = empty((int)$qty) ? 1 : (int)$qty;
        $this->options = $options;
    }


    /**
     * Insert new cart item or update qty of existence
     * @return bool
     */
    public function Insert()
    {
        if ($this->IsMax()) {
            return false;
        }

        if (!$this->Validate()) {
            return false;
        }
        $cart = $this->Read();
        if (!empty($cart)) {
            if ($this->increment_qty) {
                if ($this->ItemExists($this->id)) {
                    $item = $this->GetItem($this->id);
                    $qty = $item['qty'] + 1;
                    $data = ['qty' => $qty, 'options' => $item['options']];
                    return $this->Update($item['key'], $data);
                }
            }
            $cart[$this->key] = $this->Create();
            $_SESSION[$this->session_name] = $cart;
        } else {
            $_SESSION[$this->session_name] = [$this->key => $this->Create()];
        }
        return true;
    }


    /**
     * Create cart schema
     * @return array
     */
    private function Create()
    {
        $data = [
            'key' => $this->key,
            'id' => $this->id,
            'qty' => $this->qty,
            'options' => $this->options
        ];
        return $data;
    }

    /**
     * Update cart by key, you can get product key by GetItem
     * @param $key
     * @param $data
     * @return bool
     */
    public function Update($key, $data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        if ($this->KeyExists($key)) {
            $cart = $this->Read($key);
            $data = [
                'key' => $key,
                'id' => $cart['id'],
                'qty' => !isset($data['qty']) ? $cart['qty'] : $data['qty'],
                'options' => !isset($data['options']) ? $cart['options'] : $data['options'],
            ];
            $_SESSION[$this->session_name][$key] = $data;
            return true;
        }
        return false;
    }

    /**
     * Check if cart is beyond maximum items
     * @return bool
     */
    private function IsMax()
    {
        if ($this->CountKeys() >= $this->max_items) {
            return true;
        }
        return false;
    }


    /**
     * Count keys in cart
     * @return int
     */
    private function CountKeys()
    {
        $cart = $this->Read();
        $count = count($cart);
        return (int)$count;
    }

    /**
     * Count items in cart
     * @return int
     */
    public function Count()
    {
        $cart = $this->Read();
        $count = count($cart);
        return (int)$count;
    }

    /**
     * Check if product/item exists
     * @param $id
     * @return bool
     */
    public function ItemExists($id)
    {
        $cart = $this->Read();
        if (!empty($cart)) {
            foreach ($cart as $key => $value) {
                if ($value['id'] == $id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Generate unique cart key
     * @return int
     */
    private function KeyGenerator()
    {
        $cart = $this->Read();
        $key = mt_rand();
        if (!empty($cart)) {
            while ($this->KeyExists($key)) {
                $key = mt_rand();
            }
        }
        return $key;
    }


    /**
     * Get cart session keys to prevent key duplication
     * @return array|null
     */
    private function GetCartKeys()
    {
        $cart = $this->Read();
        $keys = null;
        if (!empty($cart)) {
            foreach ($cart as $key => $value) {
                $keys[] = $key;
            }
        }
        return $keys;
    }


    /**
     * Check if cart session key exists
     * @param $key
     * @return bool
     */
    private function KeyExists($key)
    {
        $keys = $this->GetCartKeys();
        if (!empty($keys)) {
            if (in_array($key, $keys)) {
                return true;
            }
        }

        return false;

    }


    /**
     * Read cart by session key
     * @param null $key
     * @return null
     */
    public function Read($key = null)
    {
        if (isset($_SESSION[$this->session_name])) {
            if (!empty($key)) {
                return $_SESSION[$this->session_name][$key];
            }
            return $_SESSION[$this->session_name];
        }
        return null;
    }


    /**
     * Get product by id from cart session, first one will return
     * @param $id
     * @return null
     */
    public function GetItem($id)
    {
        $cart = $this->Read();
        if ($this->ItemExists($id)) {
            foreach ($cart as $key => $value) {
                if ($value['id'] == $id) {
                    return $this->Read($key);
                }
            }
        }
        return null;
    }


    /**
     * Validation
     * @return bool
     */
    private function Validate()
    {
        if (empty($this->id) || empty($this->qty)) {
            return false;
        }
        return true;
    }

    /**
     * Remove item by cart session key
     * @param $key
     * @return bool
     */
    public function RemoveByKey($key)
    {
        if (!$this->KeyExists($key)) {
            return false;
        }
        unset($_SESSION[$this->session_name][$key]);
        return true;
    }

    /**
     * Remove item by product id
     * @param $id
     * @return bool
     */
    public function RemoveById($id)
    {
        if (!$this->ItemExists($id)) {
            return false;
        }
        $cart = $this->Read();
        foreach ($cart as $key => $value) {
            if ($value['id'] == $id) {
                unset($_SESSION[$this->session_name][$key]);
            }
        }
        return true;

    }


    /**
     * Destroy cart session
     * @return bool
     */
    public function Destroy()
    {
        if (isset($_SESSION[$this->session_name])) {
            unset($_SESSION[$this->session_name]);
            return true;
        }
        return false;
    }


}