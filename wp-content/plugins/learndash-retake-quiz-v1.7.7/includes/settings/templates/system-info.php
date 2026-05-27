<?php

// Sytems Information 
$server_info = get_server_information();

?>
<style type="text/css">

.system-info {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
  font-weight: bold;
  background: #fff;
}
.system-info th, .system-info td {
  padding: 10px;
  border: 1px solid #ccc;
}
.system-info th {
  background-color: #f2f2f2;
  font-weight: bold;
}
</style>

<div class="wn_wrap" style="margin-top: 20px;">
    <table class="system-info" border="0" cellpadding="10">
        <?php foreach ( $server_info as $key => $value ): ?>
             <tr>
                <td><?php echo $key; ?></td>
                <td><?php echo $value; ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</div>