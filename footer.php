<?php
function getFooter($location) {
    return "
        <footer>
            <p>{$location} &copy; " . date('Y') . " NazaRR</p>
        </footer>
    ";
}
?>