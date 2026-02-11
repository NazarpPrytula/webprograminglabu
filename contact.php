<?php
class ContactInformation {
    public $name;
    public $email;
    public $phone;
    public $message;
    public $dob;

    public function __construct($name, $email, $phone, $message, $dob) {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->message = $message;
        $this->dob = $dob;
    }

    public function formatAsTable() {
        return "
        <table>
            <tr><th>Name</th><td>{$this->name}</td></tr>
            <tr><th>Email</th><td>{$this->email}</td></tr>
            <tr><th>Phone</th><td>{$this->phone}</td></tr>
            <tr><th>Message</th><td>{$this->message}</td></tr>
            <tr><th>Date of Birth</th><td>{$this->dob}</td></tr>
        </table>
        ";
    }
}
