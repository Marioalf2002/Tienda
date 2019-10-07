<?php
    include 'global/config.php';
    include 'global/conexion.php';
    include 'carrito.php';
    include 'templates/cabecera.php';
?>

<?php
    //print_r($_GET);

    $Login= curl_init(LINKAPI."/v1/oauth2/token");

    curl_setopt($Login,CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($Login,CURLOPT_USERPWD, CLIENTID.":".SECRET);

    curl_setopt($Login,CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $Respuesta=curl_exec($Login);

    $objRespuesta=json_decode($Respuesta);

    $AccessToken=$objRespuesta->access_token;

    $venta=curl_init(LINKAPI."/v1/payments/payment/".$_GET['paymentID']);

    curl_setopt($venta,CURLOPT_HTTPHEADER,array("Content-Type: application/json", "Authorization: Bearer ".$AccessToken));

    curl_setopt($venta,CURLOPT_RETURNTRANSFER, TRUE);

    $RespuestaVenta=curl_exec($venta);

    $objDatosTransaccion=json_decode($RespuestaVenta);

    $state=$objDatosTransaccion->state;
    $emai=$objDatosTransaccion->payer->payer_info->email;

    $total=$objDatosTransaccion->transactions[0]->amount->total;
    $currency=$objDatosTransaccion->transactions[0]->amount->currency;
    $custom = $objDatosTransaccion->transactions[0]->custom;

    $clave=explode("#",$custom);

    $SID=$clave[0];
    $claveVenta=openssl_decrypt($clave[1],COD,KEY);

    curl_close($venta);
    curl_close($Login);

    if ($state == "approved") {
        $mensajePaypal="<h3>Pago aprobado</h3>";

        $sentencia=$pdo->prepare("UPDATE `tblventas` SET `PaypalDatos` = :PaypalDatos, `status` = 'aprobado' WHERE `tblventas`.`ID` = :ID;");
        $sentencia->bindParam(":ID",$claveVenta);
        $sentencia->bindParam(":PaypalDatos",$RespuestaVenta);
        $sentencia->execute();

        $sentencia=$pdo->prepare("UPDATE tblventas SET status='completo' WHERE ClaveTransaccion=:ClaveTransaccion AND Total=:TOTAL AND ID=:ID");
        $sentencia->bindParam(":ClaveTransaccion",$SID);
        $sentencia->bindParam(":TOTAL",$total);
        $sentencia->bindParam(":ID",$claveVenta);
        $sentencia->execute();

        $completado=$sentencia->rowCount();
        session_destroy();
    }else{
        $mensajePaypal="<h3>hay un problema con el pago de paypal</h3>";
    }

?>

<div class="jumbotron">

    <h1 class="display-4">¡Listo!</h1>

    <hr class="my-4">

    <p class="lead"><?php echo $mensajePaypal; ?></p>

    <p>
        <?php
            if ($completado>=1) {
                $sentencia=$pdo->prepare("SELECT * FROM tbldetalleventa, tblproductos WHERE tbldetalleventa.IDPRODUCTO=tblproductos.id AND tbldetalleventa.IDVENTA=:ID");
                $sentencia->bindParam(":ID",$claveVenta);
                $sentencia->execute();

                $listaProductos=$sentencia->fetchAll(PDO::FETCH_ASSOC);

                //print_r($listaProductos);
            }
        ?>
        <div class="row">
            <?php foreach ($listaProductos as $producto){ ?>
            <div class="col-2">
                <div class="card">
                    <img class="card-img-top" src="<?php echo $producto['imagen']; ?>">
                    <div class="card-body">
                        <p class="card-text"><?php echo $producto['nombre']; ?></p>
                        <?php if($producto['DESCARGADO']<LIMITEDESCARGAS) {?>
                            <form action="descargas.php" method="post">
                                <input type="hidden" name="IDVENTA" id="" value="<?php echo openssl_encrypt($claveVenta, COD, KEY); ?>">
                                <input type="hidden" name="IDPRODUCTO" id="" value="<?php echo openssl_encrypt($producto['IDPRODUCTO'], COD, KEY); ?>">
                                <button class="btn btn-success" type="submit">Descargar</button>
                            </form>
                        <?php }else{ ?>
                            <button class="btn btn-success" type="button" disabled >Descargar</button>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </p>
</div>

<?php
    include 'templates/pie.php';
?>