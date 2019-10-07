<?php
    include 'global/config.php';
    include 'global/conexion.php';
    include 'carrito.php';
?>

<?php

    if ($_POST) {
        $IDVENTA= openssl_decrypt($_POST['IDVENTA'],COD,KEY);
        $IDPRODUCTO= openssl_decrypt($_POST['IDPRODUCTO'],COD,KEY);

        print_r("IDVENTA: ".$IDVENTA);
        print_r("IDPRODUCTO: ".$IDPRODUCTO);

        $sentencia=$pdo->prepare("SELECT * FROM `tbldetalleventa` WHERE IDVENTA=:IDVENTA AND IDPRODUCTO=:IDPRODUCTO AND DESCARGADO<".LIMITEDESCARGAS);
        $sentencia->bindParam(":IDVENTA",$IDVENTA);
        $sentencia->bindParam(":IDPRODUCTO",$IDPRODUCTO);
        $sentencia->execute();

        $listaProductos=$sentencia->fetchAll(PDO::FETCH_ASSOC);

        //print_r($listaProductos);
        if ($sentencia->rowCount()>0) {

            echo "Archivo en descarga...";

            $nombreArchivos="archivos/".$listaProductos[0]['IDPRODUCTO'].".pdf";
            $nuevoNombreArchivo=$_POST['IDVENTA'].$_POST['IDPRODUCTO'].".pdf";
            echo $nuevoNombreArchivo;

            header("Content-Transfer-Encoding: binary");
            header("Content-type: application/force-download");
            header("Content-Disposition: attachment; filename=$nuevoNombreArchivo");
            readfile("$nombreArchivos");

            $sentencia=$pdo->prepare("UPDATE `tbldetalleventa` set DESCARGADO=DESCARGADO+1 WHERE IDVENTA=:IDVENTA AND IDPRODUCTO=:IDPRODUCTO");
            $sentencia->bindParam(":IDVENTA",$IDVENTA);
            $sentencia->bindParam(":IDPRODUCTO",$IDPRODUCTO);
            $sentencia->execute();

        }else{
            include 'templates/cabecera.php';
                echo "<br><br><br><br><h2>Tus descargas se agotaron<h2>";
            include 'templates/pie.php';
        }
    }
?>