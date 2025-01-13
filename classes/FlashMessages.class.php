<?php

class FlashMessages
{
    private $msgTypes = array('info', 'warning', 'success', 'danger');
    private $msgWrapper = "<p class='bs-callout bs-callout-%s alert' role='alert'><button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>\n%s\n</p>";
    private $msgBefore = '';
    private $msgAfter = '<br>';
    private $msgClass = '';

    public function __construct()
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        if (!array_key_exists('flash_messages', $_SESSION)) {
            $_SESSION['flash_messages'] = array();
        }
    }

    public function add($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            return false;
        }
        if (!isset($type, $message[0])) {
            return false;
        }

        if (!in_array($type, $this->msgTypes, true)) {
            die('"'.strip_tags($type).'" is not a valid message type!');
        }

        if (!array_key_exists($type, $_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'][$type] = array();
        }

        $_SESSION['flash_messages'][$type][] = $message;

        return true;
    }

    public function display($type = 'all', $print = true)
    {
        if (!isset($_SESSION['flash_messages'])) {
            return false;
        }

        $data = '';
        $messages = '';
        // Print a certain type of message?
        if (in_array($type, $this->msgTypes, true)) {
            foreach ($_SESSION['flash_messages'][$type] as $msg) {
                $messages .= $this->msgBefore.$msg.$this->msgAfter;
            }

            $data .= sprintf($this->msgWrapper, $this->msgClass, $type, $messages);

            // Clear the viewed messages
            $this->clear($type);

            // Print ALL queued messages
        } elseif ($type === 'all') {
            foreach ($_SESSION['flash_messages'] as $msgType => $msgArray) {
                $messages = '';
                foreach ($msgArray as $msg) {
                    $messages .= $this->msgBefore.$msg.$this->msgAfter;
                }
                $data .= sprintf($this->msgWrapper, $msgType, $messages);
            }

            // Clear all messages
            $this->clear();

            // Invalid Message Type?
        } else {
            return false;
        }

        // Print everything to the screen or return the data
        if ($print) {
            echo $data;
        } else {
            return $data;
        }

        return false;
    }

    public function hasMessages()
    {
        $type = null;

        if (!is_null($type)) {
            if (!empty($_SESSION['flash_messages'][$type])) {
                return $_SESSION['flash_messages'][$type];
            }
        } else {
            foreach ($this->msgTypes as $type) {
                if (!empty($_SESSION['flash_messages'])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function clear($type = 'all'): bool
    {
        if ($type === 'all') {
            unset($_SESSION['flash_messages']);
        } else {
            unset($_SESSION['flash_messages'][$type]);
        }

        return true;
    }
}
