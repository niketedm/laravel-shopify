<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisaninweb\SoapWrapper\SoapWrapper;
use PHPShopify;
use App\Post;
use SoapClient;
use Illuminate\Pagination\Paginator;


class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

        $this->config = array(
            'ShopUrl' => $_ENV['SHOPIFY_URL'],
            'ApiKey' => $_ENV['SHOPIFY_API_KEY'],
            'Password' => $_ENV['SHOPIFY_PASSWORD'],
        ); 

        $this->redirectUrl = $_ENV['SHOPIFY_REDIRECT_URI'];
        $this->scopes = 'read_products,write_products,read_script_tags,write_script_tags';       
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {        
        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        $filters = array(
            //'status' => 'any', // open / closed / cancelled / any (Default: open)
            'limit' => '50'
        );
        $orders = $shopify->Order->get($filters);

        //echo '<pre>';
        //var_dump($orders);
        //echo '</pre>';

        return view('order.index', array('orders'=>$orders) );
    }
    public function fulfilled()
    {        
        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        $filters = array(
            'status' => 'any', // open / closed / cancelled / any (Default: open)
            'limit' => '100'
        );
        $orders = $shopify->Order->get($filters);

        //  echo '<pre>';
        //  var_dump($orders);
        //  echo '</pre>';

        return view('order.fulfilled', array('orders'=>$orders) );
    }

    public function view(Request $request) {

        $orderId = $request->route('id');

        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        $order = $shopify->Order($orderId)->get();
        
        //echo '<pre>';
        //print_r($order);
        //echo '</pre>';

        return view('order.view', array('order'=>$order) );

    }

    public function create()
    {
        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);

        $customers = $shopify->Customer->get();
        $products = $shopify->Product->get();

        return view('order.create', array(
            'products'  => $products,
            'customers' => $customers
        ));
    }

    public function post(Request $request)
    {
    	
        $request->validate([
            'email' => 'required',
            'variant_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'shipping_first_name' => 'required',
            'shipping_last_name' => 'required',
            'address1' => 'required',
        ]);

        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        
        $arrOrder = array("email" => $request->get('email'),
                    "fulfillment_status" => $request->get('fulfillment_status'),
                    "send_receipt" => $request->get('send_receipt'),
                    "send_fulfillment_receipt" => $request->get('send_fulfillment_receipt'),
                    "line_items" => array
                    (
                        array
                        (
                            "variant_id" => $request->get('variant_id'),
                            "quantity" => $request->get('quantity'),
                        )
                    ),
                    "tax_lines" => array
                    (
                        array
                        (
                            "price" => 6.0,
					        "rate" => 0.06,
					        "title" => "VAT",
                        )
                    ),
                    "shipping_address" => array(
                    	  "first_name" => $request->get('shipping_first_name'),
					      "last_name" => $request->get('shipping_last_name'),
					      "address1" => $request->get('address1'),
					      "phone" => $request->get('shipping_phone'),
					      "city" => $request->get('city'),
					      "province" => $request->get('province'),
					      "country" => $request->get('country'),
					      "zip" => $request->get('zip')
                    )
                );

        $response = $shopify->Order->post($arrOrder);        
        
        //var_dump($response);
        header_remove();

        return redirect()->route('orders')
                            ->with('status','Order created successfully.');  

    }

     public function edit(Request $request) {

        $orderId = $request->route('id');

        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);

        $customers = $shopify->Customer->get();
        $order = $shopify->Order($orderId)->get();
        
        return view('order.edit', array('order'=>$order, 'customers'=>$customers) );

    }

    public function put(Request $request)
    {
        // Process Edit
        $request->validate([
            'email' => 'required',           
            'shipping_first_name' => 'required',
            'shipping_last_name' => 'required',
            'address1' => 'required',
        ]);

        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        
        $arrOrder = array("email" => $request->get('email'),
                    "phone" => $request->get('phone'),                    
                    "shipping_address" => array(
                    	  "first_name" => $request->get('shipping_first_name'),
					      "last_name" => $request->get('shipping_last_name'),
					      "address1" => $request->get('address1'),
					      "phone" => $request->get('shipping_phone'),
					      "city" => $request->get('city'),
					      "province" => $request->get('province'),
					      "country" => $request->get('country'),
					      "zip" => $request->get('zip')
                    )
                );

        $response = $shopify->Order($request->get('id'))->put($arrOrder);        
        
        //var_dump($response);
        header_remove();

        return redirect()->route('orderview', [$request->get('id')])
                            ->with('status','Order updated successfully.');  
    }

    public function delete(Request $request)
    {
        $orderId = $request->route('id');

        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        $shopify->Order($orderId)->delete();
        
        header_remove();

        return redirect()->route('orders')
                            ->with('status','Order has been deleted.');  
    }
    public function crearEnvio(Request $request)
    {
       //1- obtener los datos del pedido
       $orderId = $request->route('id');
    
        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);

        $customers = $shopify->Customer->get();
        $order = $shopify->Order($orderId)->get();

        // cliente
        $telefono = str_replace(' ', '', $order['shipping_address']['phone']);
        $telefono = substr($telefono, 0, 9);
        $correo = $order['email'];
        $nombrecompleto = $order['shipping_address']['first_name'] . ' ' . $order['shipping_address']['last_name'];
        $calle = $order['shipping_address']['address1'];
        $departamento = strtoupper($order['shipping_address']['address2']);
        $localidad = $order['shipping_address']['city'];
        $cedula = $order['shipping_address']['company'];
        //numero
        $callenumero = array_filter(preg_split("/\D+/", $calle));
        $numerocasa = reset($callenumero);
        
        // pedido
        $peso = $order['total_weight'];
        $referencia = 'Pedido ' . $order['order_number'];
        
        //Llamada a coreo uruguay
        $wsdl = "http://ahivatest.correo.com.uy/web/CargaMasivaServicev4?wsdl";
        $client = new SoapClient($wsdl, array(  'soap_version' => SOAP_1_1,'trace' => true,)); 
        $namespace = 'http://schemas.xmlsoap.org/soap/envelope/'; 

        $params = array (
            "arg0" => '216778010014',
            "arg1" => 'q1w2e3r4',
            "arg4" => array(
                    'clave' => 'autoadhesiva',
                    'valor' => 'si'
            ),
            "arg5" => array(
                'cedulaDestinatario' => $cedula, 
                'datosdevolucion' => array(
                    'calle' => 'Jose Ignacio',
                    'departamento' => 'La Juanita',
                    'localidad' => 'Maldonado',
                    'nroPuerta' => '582'
                ),
                'destinatario' => array(
                    'celular' => $telefono,
                    'mail' => $correo,
                    'nombre' => $nombrecompleto
                ),
                'lugarEntrega' => array(
                    'calle' => $calle,
                    'departamento' => $departamento,
                    'localidad' => $localidad,
                    'manzana' => '',
                    'nroApto' => '',
                    'nroPuerta' => $numerocasa,
                    'observacionesDireccion' => '',
                    'oficinaCorreo' => '',
                    'solar' => ''
                ),
                'paquetesSimples' => array(
                    'almacenamiento' => '10',
                    'empaque' => '0',
                    'motivodevolucion' => '',
                    'peso' => $peso,
                    'referencia' => $referencia,
                    'responsableServEntrega' => 'DESTINATARIO'
                ),
                'soloDestinatario' => '0'
            ),

        );
        //echo '<pre>';
        //die(var_dump($params));
        $response = $client->__soapCall('cargaMasiva'  , array($params));
        
        $descripcionRespuesta = $response->return->descripcionRespuesta;
        $codigoRespuesta = $response->return->codigoRespuesta;
        $esError = $response->return->esError;
        
        if (empty($esError)) {
            echo $descripcionRespuesta;
            $tracking = $response->return->envios->codigostrazabilidad;
            $etiqueta = $response->return->envios->etiquetasGeneradas;
            echo $tracking;

            $destination = '../storage/app/public/'.$tracking.'.pdf';
            $file = fopen($destination, "w+");
            fputs($file, $etiqueta);
            fclose($file);
            $filename = $tracking.'.pdf';
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$filename");
            header("Content-Type: application/pdf");
            header("Content-Transfer-Encoding: binary");
            readfile($destination);
            //https://www.correo.com.uy/seguimientodeenvios
            // $shopify->Order($order->order_id)->Fulfillment->post([
            //     "location_id" => $shopify->Location->get()[0]['id'],
            //     "tracking_number" => $tracking,
            //     "tracking_urls" => ['https://www.correo.com.uy/seguimientodeenvios],
            //     "notify_customer" => true
            // ]);

        } else {
            echo $descripcionRespuesta;
        };
        
        








       //2- Enviar los datos a servicio soap de correo uruguay
       //3- retornar la respuesta y guardar el codigo de tracking
       //4- generar fulfill de shopify y guardar el tracking en la orden
    }


}
