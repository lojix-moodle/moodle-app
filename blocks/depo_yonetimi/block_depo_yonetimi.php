<?php

class block_depo_yonetimi extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_depo_yonetimi');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '<a href="/blocks/depo_yonetimi/actions/depolar.php" class="btn btn-primary">Depoları Yönet</a>';
        $this->content->footer = '';

        return $this->content;
    }
}
