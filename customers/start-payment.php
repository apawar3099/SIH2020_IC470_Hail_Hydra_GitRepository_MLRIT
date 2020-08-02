<?php
require_once "../utils.php";
check_session('customers');
function get_payment_link($price)
{
    $stmt=$GLOBALS['conn']->query('SELECT * FROM customers WHERE id='.$_SESSION['id']);
    $r=$stmt->fetch(PDO::FETCH_ASSOC);
    $ch = curl_init();
    $fields = array();
    $fields["type"] = 'link';
    $fields["amount"] = $price*100;
    $fields["description"] = 'Payment for water tanker.';
    $fields["customer"] = array('name'=>$r['name'],'email'=>$r['email'],'contact'=>$r['phone']);
    $fields["currency"] = 'INR';
    $fields["expire_by"] = time()+3600;
    $fields["callback_url"] = 'http://localhost/sih/customers/process-payment.php';
    $fields["callback_method"] = 'get';
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/invoices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "rzp_test_ucJmNTft2erwgJ:bMqrpkAVHF57AgI3ErclS7Ex");
    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $data = curl_exec($ch);

    if (empty($data) OR (curl_getinfo($ch, CURLINFO_HTTP_CODE != 200))) {
       return FALSE;
    } else {
        $d= json_decode($data, TRUE);
        $stmt=execSQL('SELECT id FROM waterproviders WHERE state=? AND city=? AND zone=? AND ward=? ORDER BY RAND() LIMIT 1',array($r['state'],$r['city'],$r['zone'],$r['ward']));
        $w=$stmt->fetch(PDO::FETCH_ASSOC);
        $stmt=execSQL('INSERT INTO orders VALUES(?,?,?,?,?,?,?,?,?,?)',array(null,$_SESSION['id'],$w['id'],null,$_POST['date'],$_POST['quantity'],$price,$d['id'],null,stripslashes($d['short_url'])));
        return stripslashes($d['short_url']);
    }
    curl_close($ch);
    return false;
}
$data=get_payment_link(100);
if ($data!=FALSE)
{
    header('location:'.$data);
}
else
{
    $_SESSION['msg']='<div class="alert alert-danger">Error</div><br>';
    header('location:dashboard.php');
}
?>