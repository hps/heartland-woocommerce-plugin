<?php
$url = trim(get_permalink());
if (substr($url, -1) == '/') {
    $url = substr($url, 0, strlen($url) - 1);
};
?>
<div style="float: right">
    <form id="pp" method="post" action="<?php echo $url; ?>?paypalexpress_initiated=true">
        <input type="hidden" name="paypalexpress_initiated" value="true"/>
        <a href="#" onclick="document.getElementById('pp').submit();">
            <img src="https://www.paypalobjects.com/en_US/i/btn/x-click-but6.gif">
        </a>
    </form>
</div>
