<?php
namespace block_depo_yonetimi\form;

require_once("$CFG->libdir/formslib.php");

class kategori_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        // Kategori ismi alanı
        $mform->addElement('text', 'isim', 'Kategori İsmi');
        $mform->setType('isim', PARAM_TEXT);
        $mform->addRule('isim', 'Bu alan zorunludur', 'required', null, 'client');

        // Butonlar
        $this->add_action_buttons(true, 'Kaydet');
    }
}
