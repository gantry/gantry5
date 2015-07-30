<?php
/**
 * Helper class that allows us to get flat array of comments from TimberPost
 */

class G5TimberComment extends TimberComment {
    public function is_child() {
        return false;
    }
}
