<?php

namespace block_depo_yonetimi\form;

use moodleform;

require_once("$CFG->libdir/formslib.php");

class urun_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'depoid');
        $mform->setType('depoid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('productname', 'block_depo_yonetimi'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'adet', get_string('quantity', 'block_depo_yonetimi'));
        $mform->setType('adet', PARAM_INT);
        $mform->addRule('adet', null, 'required', null, 'client');

        $this->add_action_buttons();
    }
}
