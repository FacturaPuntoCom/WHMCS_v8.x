<?php

//namespace WHMCS\Module\Addon\Facturacom;
use WHMCS\Module\Addon\Facturacom\Admin\CoreModule;

ob_start();

require_once '../../../../init.php';

/**
 * Handler for Json Ajax Calls from CUSTOMER AREA
 * @author Paul Soberanes  <@soberanees>
 * @copyright (c) Octuber 2015, Factura.com
 */
header('Access-Control-Allow-Methods:POST');

$resultado = array("Error" => "Fail");

if (isset($_POST['function'])) {
    $func = $_POST['function'];
    if(in_array($func, ['cancelInvoice', 'sendInvoice', 'loadInvoicesTable', 'getLocation', 'getClient', 'createInvoice'])){
        try {
            $resultado = $func();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log('Factura.com apihandler ' . $func . ': ' . $e->getMessage());
            $resultado = apiErrorResponse($e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : null);
        } catch (\Throwable $e) {
            error_log('Factura.com apihandler ' . $func . ': ' . $e->getMessage());
            $resultado = apiErrorResponse(null);
        }
    }
}

// Discard any PHP notices/deprecations printed so the response is always valid JSON
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultado);

/**
 * @param String|null $apiBody
 * @return Array
 */
function apiErrorResponse($apiBody)
{
    $message = 'No fue posible completar la solicitud con Factura.com.';
    $decoded = is_string($apiBody) ? json_decode($apiBody, true) : null;
    if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
        $message = $decoded['message'];
    }

    return array(
        'status' => 'error',
        'response' => 'error',
        'message' => $message,
    );
}

/**
 * Cancel invoice in Factura.com system
 *
 * @param Global $_POST
 * @return Array
 */
function cancelInvoice()
{

    $CoreModule = new CoreModule;
    $response = $CoreModule->cancelInvoice($_POST);

    return $response;
}

/**
 * Send invoice via email to customer
 *
 * @param Global $_POST
 * @return Array
 */
function sendInvoice()
{
    $uid = $_POST['uid'];

    $CoreModule = new CoreModule;
    $response = $CoreModule->sendInvoiceEmail($_POST);

    return $response;
}

/**
 * Load and display in invoices table admin section
 *
 * @param Global $_POST
 * @return Array
 */
function loadInvoicesTable()
{
    $CoreModule = new CoreModule;
    return $CoreModule->getInvoicesFacturacomAll();
}

/**
 * Get location by postal code
 *
 * @param Global $_POST
 * @return Array
 */
function getLocation()
{
    $CoreModule = new CoreModule;
    return $CoreModule->getLocation($_POST['cp']);
}

/**
 * Get Factura.com client information
 *
 * @param Global $_POST
 * @return Array
 */
function getClient()
{
    $CoreModule = new CoreModule;
    return $CoreModule->getClientFacturacom($_POST['rfc']);
}

/**
 * Update client information and create Invoice
 *
 * @param Global $_POST
 * @return Array
 */
function createInvoice()
{

    $CoreModule = new CoreModule;
    $ItemsOrder = $CoreModule->getInvoiceItems($_POST['orderNum']);

    $orderNum = $_POST['orderNum'];
    $orderItems = $ItemsOrder;
    $clientData = $_POST['clientData'];
    $serieInvoices = $_POST['serieInvoices'];
    $clientW = $_POST['clientW'];
    $paymentMethod = $_POST['paymentMethod'];
    $numerocuenta = $_POST['numerocuenta'];
    $usoCFDI = $_POST['UsoCFDI'];

    return $CoreModule->createInvoice($orderNum, $orderItems, $clientData, $serieInvoices, $clientW, $paymentMethod, $numerocuenta, $usoCFDI);
}
