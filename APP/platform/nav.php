<?php
    if(isset($_SESSION['switcher']))
    {
        echo '<li style="color:#d9534f;"><a href="am.php"><span style="color:#d9534f;" class="fa fa-home"></span><span class="sidebar-title"><b>Admin</b></span></a></li>';
?>
<li style="color:#d9534f;" <?php if(isset($_GET['dashboard'])){echo 'class="active"';} ?>>
<a href="dashboard.php">
    <span style="color:#d9534f;" class="fa fa-dashboard"></span>
    <span class="sidebar-title">World Map</span>
</a>
<li style="color:#d9534f;" <?php if(isset($_GET['explorer'])){echo 'class="active"';} ?>>
    <a href="explorer.php">
        <span style="color:#d9534f;" class="fa fa-flask"></span>
        <span class="sidebar-title">Bid Explorer</span>
    </a>
</li>
<li style="color:#d9534f;" <?php if(isset($_GET['banners'])){echo 'class="active"';} ?>>
<a href="banners.php">
    <span style="color:#d9534f;" class="fa fa-image"></span>
    <span class="sidebar-title">Banners</span>
</a>
</li>
<?php } ?>
</li>
<li style="color:#ffbf00;" <?php if(isset($_GET['campaigns'])){echo 'class="active"';} ?>>
<a href="campaigns.php">
    <span class="fa fa-table"></span>
    <span class="sidebar-title">Campaigns</span>
</a>
</li>
<li style="color:#ffbf00;" <?php if(isset($_GET['inventory'])){echo 'class="active"';} ?>>
    <a href="inventory.php">
        <span class="fa fa-pie-chart"></span>
        <span class="sidebar-title">Inventory</span>
    </a>
</li>
<li style="color:#ffbf00;" <?php if(isset($_GET['finance'])){echo 'class="active"';} ?>>
<a href="finance.php">
    <span class="fa fa-dollar"></span>
    <span class="sidebar-title">Finance</span>
</a>
</li>
<li style="color:#ffbf00;" <?php if(isset($_GET['account'])){echo 'class="active"';} ?>>
<a href="account.php">
    <span class="fa fa-user"></span>
    <span class="sidebar-title">Account</span>
</a>
</li>
<li style="color:#ffbf00;">
<a href="logout.php">
    <span class="fa fa-power-off"></span>
    <span class="sidebar-title">Logout</span>
</a>
</li>