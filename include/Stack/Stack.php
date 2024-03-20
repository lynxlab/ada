<?php

/**
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 * @version
 * @package
 * @license
 * @copyright (c) 2009 Lynx s.r.l.
 */

namespace Lynxlab\ADA\Main\Stack;

/**
 * The stack class is an OO-wrapper around the array php native type.
 *
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */
class Stack
{
    /**
     *
     * Attributes
     *
     */
    public $stack_ar;   // the stack is an array (too silly? ok, but I have pop and push, for free)


    /**
     * Stack constructor.
     *
     * @param $debuginfo additional debug info, such as the last query
     *
     * @access public
     *
     */
    public function __construct()
    {
        $this->stack_ar = [];
    }

    /**
     * Check if the stack is empty
     *
     * @access public
     *
     * @return 1 if the stack is empty, 0 otherwise
     *
     */
    public function isEmpty()
    {
        if (sizeof($this->stack_ar)) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Return the size of the stack
     *
     * @access public
     *
     * @return the number of element in the stack
     *
     */
    public function get_size()
    {
        return sizeof($this->stack_ar);
    }


    /**
     * Push an element onto the stack
     *
     * @param $element - the hash containing the name of the function and the
     * parameters array
     *
     * @access public
     *
     */
    public function push($element)
    {
        array_push($this->stack_ar, $element);
    }

    /**
     * Pop an element from the stack
     *
     * @access public
     *
     * @return the element popped out (a hash)
     */
    public function pop()
    {
        return array_pop($this->stack_ar);
    }

    /**
     * DElete the content of the stack
     *
     * @access public
     *
     */
    public function delete()
    {
        while (sizeof($this->stack_ar)) {
            $this->pop();
        }
    }
}
