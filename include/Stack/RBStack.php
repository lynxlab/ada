<?php

/**
 *
 * @author
 * @version
 * @package
 * @license
 * @copyright (c) 2009 Lynx s.r.l.
 */

/**
 * RBStack is a class which extends the Stack class and allows a more refined handling
 * of transactions. In particular nested transactions are possible by use of the markers.
 * If a beginTransaction is called and the rollback stack is not empty,
 * then a marker is set to sign the point where a rollback must stop or
 * the point untill the rollback segment must be deleted in a commit instruction.
 * After the rollback or the commit, the marker is released.
 *
 * This allows multiple, nested transactions to be handled.
 *
 **/

namespace Lynxlab\ADA\Main\Stack;

class RBStack extends Stack
{
    /**
     *
     * Attributes
     *
     */
    // the markers are in a stack itself (a bit clumsy, but useful!)
    public $marker_ar;

    public function __construct()
    {
        parent::__construct();
        $this->marker_ar = new Stack();
    }

    /**
     * add a marker on top of the markers stack
     *
     * @access public
     *
     */
    public function insertMarker()
    {
        $pos = sizeof($this->stack_ar);
        if ($pos != 0) {
            $this->marker_ar->push($pos);
        }
    }

    /**
     * remove a marker from the markers stack
     *
     * @access public
     *
     * @return the value of the marker or zero if empty
     *
     */
    public function removeMarker()
    {
        if (!$this->marker_ar->isEmpty()) {
            return $this->marker_ar->pop();
        } else {
            return 0;
        }
    }
}
