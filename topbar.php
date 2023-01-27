<?php
require "config.php";
$messages=0;
$fake=$conn->query("SELECT message_state, message_date from messages_table where message_State='on'")->num_rows;
$messages= $messages+$fake;
echo "
<div data-component='navbar'>

<nav class='navbar p-0 fixed-top'>
      
  
  <div><a class='navbar-brand px-1' href='index.php'><img src='silk.png' class='d-inline-block mt-1' alt='AgentFire Logo' height='40'></a>

  <div class='right-links float-right mr-4'>
    
    
    
    
    <div class='d-inline dropdown mr-3'>
      <a class='dropdown-toggle' id='notifications' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' href='#'>
      
      <span>";
      if ($messages>0){
        echo $messages;
      
      }
      echo "</span>
      
      <i class='fa fa-bell'></i></a>
      <div class='dropdown-menu dropdown-menu-right rounded-0 pt-0' aria-labelledby='notifications'> 
      
        <div class='list-group'>
          <div class='lg'>";
          if ($messages>0 & $conn->query("SELECT id from messages_table where message_state='on' and message_class='fake_subscribers'")->num_rows>0){
             echo "
            <a href='p2psubs.php?unauthorized_subs=no_service' class='list-group-item list-group-item-action flex-column align-items-start active'>
              <h5 class='mb-1'>";
              if ($fake>0) {
                echo "Unauthorized subscribers detected";
                $message_date=$conn->query("SELECT message_date from messages_table where message_class='fake_subscribers'")->fetch_assoc()["message_date"];
              
              echo "</h5>
              <p class='mb-0'>".$message_date."</p>"; }
              echo "
            </a>"; }

            if ($messages>0 & $conn->query("SELECT id from messages_table where message_state='on' and message_class='no_group_subscribers'")->num_rows>0){
              echo "
             <a href='p2psubs.php?unauthorized_subs=no_group' class='list-group-item list-group-item-action flex-column align-items-start active'>
               <h5 class='mb-1'>";
               if ($fake>0) {
                 echo "Customers with no subscriber group configured";
                 $message_date=$conn->query("SELECT message_date from messages_table where message_class='no_group_subscribers'")->fetch_assoc()["message_date"];
               
               echo "</h5>
               <p class='mb-0'>".$message_date."</p>"; }
               echo "
             </a>"; }
            echo "
            
            
          </div> <!-- /.lg -->
        </div> <!-- /.list group -->
      </div> <!-- /.dropdown-menu -->
    </div> <!-- /.dropdown -->
    
    <div class='d-inline dropdown mr-3'>
      <a class='dropdown-toggle' id='messages' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' href='#'><i class='fa fa-envelope'></i></a>
      <div class='dropdown-menu dropdown-menu-right rounded-0 text-center' aria-labelledby='messages'>
        <a class='dropdown-item'>There are no new messages</a>
      </div> <!-- /.dropdown-menu -->
  </div> <!-- /.dropdown -->
    
    
    <div class='d-inline dropdown'>
      <a class='dropdown-toggle' id='messages' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' href='#'>
        <img src='http://1.gravatar.com/avatar/47db31bd2e0b161008607d84c74305b5?s=96&d=mm&r=g'>
      </a>
      <div class='dropdown-menu dropdown-menu-right rounded-0' aria-labelledby='messages'>
        <a class='dropdown-item' href='#'>Edit my profile</a>
        <a class='dropdown-item' href='#'>Log Out</a>
      </div> <!-- /.dropdown-menu -->
    </div> <!-- /.dropdown -->
    
  </div> <!-- /.right-links -->
  
  </div>
  
  
  
  
  
  
  
  
  
  
  
  
      </div></div>
</nav>
</div> <!-- END TOP NAVBAR -->";