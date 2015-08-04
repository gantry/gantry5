<?php
/**
 * Helper class that allows us to get flat array of comments from TimberPost
 */

if( class_exists( 'TimberComment' ) ) {
    class G5TimberComment extends TimberComment {
        public function is_child() {
            return false;
        }
    }
}
