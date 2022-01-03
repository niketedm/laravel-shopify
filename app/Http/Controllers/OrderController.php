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
            'status' => 'any', // open / closed / cancelled / any (Default: open)
            'limit' => '140'
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
        //die(var_dump($order));
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

        // quitar acentos
        // $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        //                     'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        //                     'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        //                     'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        //                     'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        // $str = strtr( $str, $unwanted_array );

        // cliente
        $caracteresInvalidos = array(" ", "+");
        $telefono = str_replace($caracteresInvalidos, '', $order['shipping_address']['phone']);
        $telefono = substr($telefono, 0, 9);
        $correo = $order['email'];
        $nombrecompleto = $order['shipping_address']['first_name'] . ' ' . $order['shipping_address']['last_name'];
        $calle = $order['shipping_address']['address1'];
        $departamento = strtoupper($order['shipping_address']['city']);
        $localidad = strtoupper($order['shipping_address']['address2']);
        $cedula = $order['shipping_address']['company'];
        //numero
        $callenumero = array_filter(preg_split("/\D+/", $calle));
        $numerocasa = reset($callenumero);
        
        // pedido
        $peso = $order['total_weight'] / 100;
        $referencia = 'Pedido ' . $order['order_number'];
        
        //2- Enviar los datos a servicio soap de correo uruguay
        $wsdl = "https://ahiva.correo.com.uy/web/CargaMasivaServicev4?wsdl";
        $client = new SoapClient($wsdl, array(  'soap_version' => SOAP_1_1,'trace' => true,)); 
        $namespace = 'http://schemas.xmlsoap.org/soap/envelope/'; 

        $params = array (
            "arg0" => 'Guadalupecid',
            "arg1" => '3693',
            "arg2" => '3693',
            "arg3" => '0',
            "arg4" => array(
                    'clave' => 'autoadhesiva',
                    'valor' => 'si'
            ),
            "arg5" => array(
                'cedulaDestinatario' => $cedula, 
                'datosdevolucion' => array(
                    'calle' => 'Jose Ignacio',
                    'departamento' => 'MALDONADO',
                    'localidad' => 'JOSE IGNACIO',
                    'nroPuerta' => '582',
                    'codigoPostal' => '20000'
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
        
        //3- retornar la respuesta y guardar el codigo de tracking
        $descripcionRespuesta = $response->return->descripcionRespuesta;
        $codigoRespuesta = $response->return->codigoRespuesta;
        $esError = $response->return->esError;
        
        if (empty($esError)) {
            //echo $descripcionRespuesta;
            $tracking = $response->return->envios->codigostrazabilidad;
            $etiqueta = $response->return->envios->etiquetasGeneradas;
            //echo $tracking;
            //Descargar etiqueta generada
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
            
            //4- generar fulfill de shopify y guardar el tracking en la orden
            //https://www.correo.com.uy/seguimientodeenvios
            try { 
                PHPShopify\ShopifySDK::config($this->config);
                PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

                $shopify = new PHPShopify\ShopifySDK($this->config);
                $lineItems =  $order['line_items'];
                //echo '<pre>';
                //print_r($lineItems);
                $data = [
                    'location_id' => $shopify->Location->get()[0]['id'],
                    "tracking_url" => 'https://ahiva.correo.com.uy/servicioConsultaTntIps-web/SeguimientoJSNuevo?codigoPieza=' . $tracking,
                    'tracking_number'=> $tracking,
                    "line_items" => $lineItems,
                    "notify_customer" =>true,
                ];
                //echo '<pre>';
                //print_r($data);
                $shopify->Order($orderId)->Fulfillment->post($data);
                
                header_remove();
                return redirect()->route('orders')
                            ->with('status','Envio creado con exito.');
        
            }catch (\Exception $e) {

                return $e->getMessage();
            }
            
            

        } else {
            echo $descripcionRespuesta;
        };
       
    }
    public function imprimirEtiqueta(Request $request)
    {
        $orderId = $request->route('id');
    
        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);

        $order = $shopify->Order($orderId)->get();
        $filters = array(
            'status' => 'complete', // open / closed / cancelled / any (Default: open)
        );
        $fulfill = $shopify->Order($orderId)->Fulfillment()->get($filters);
        $ultimo = $fulfill[count($fulfill)-1];
        $tracking = $ultimo['tracking_number'];
        $filename = $tracking.'.pdf';
        
        $destination = '../storage/app/public/'.$tracking.'.pdf';
        
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        readfile($destination);
        

        die(var_dump($destination));
        
        if (! file_exists($destination)) {
            echo 'error';
        }
        

        
        
       
    }


}
