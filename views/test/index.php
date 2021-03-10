<p>Hello test</p>
<ul>
<?php
 foreach ($password as $el):
?>
    <li><?=$el['ip']?>  <?=$el['user']?> <?=$el['password']?></li>
    <?php
    endforeach;
    ?>
</ul>