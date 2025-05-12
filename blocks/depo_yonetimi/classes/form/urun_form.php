<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class urun_form extends moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', 'Ürün Adı');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Gerekli', 'required', null, 'client');

        $mform->addElement('text', 'adet', 'Adet');
        $mform->setType('adet', PARAM_INT);
        $mform->addRule('adet', 'Gerekli', 'required', null, 'client');

        $mform->addElement('hidden', 'depoid');
        $mform->setType('depoid', PARAM_INT);

        $mform->addElement('hidden', 'index');
        $mform->setType('index', PARAM_INT);

        $this->add_action_buttons();
    }
}
