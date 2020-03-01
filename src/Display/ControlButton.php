<?php

namespace SleepingOwl\Admin\Display;

use Illuminate\View\View;

class ControlButton extends ControlLink
{
    /**
     * @var string|View
     */
    protected $view = 'column.control_button';

    /**
     * @var string
     */
    protected $method = 'post';

    /**
     * @var bool
     */
    protected $confirm = false;

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param $confirm
     *
     * @return $this
     */
    public function setConfirm( $confirm  )
    {
        $this->confirm = $confirm;

        return $this;
    }

    /**
     * @return bool
     */
    public function getConfirm(  )
    {
        return $this->confirm;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return parent::toArray() + [
            'method' => $this->getMethod(),

            'confirm' => $this->getConfirm()
        ];
    }
}
