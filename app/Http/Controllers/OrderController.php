<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisaninweb\SoapWrapper\SoapWrapper;
use PHPShopify;
use App\Post;
use SoapClient;
use Illuminate\Pagination\LengthAwarePaginator;
use AlejoASotelo\Andreani;


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
    public function index(Request $request)
    {        
        PHPShopify\ShopifySDK::config($this->config);
        PHPShopify\AuthHelper::createAuthRequest($this->scopes, $this->redirectUrl, null, null, true);

        $shopify = new PHPShopify\ShopifySDK($this->config);
        $filters = array(
            'status' => 'any', // open / closed / cancelled / any (Default: open)
            'limit' => '200'
        );
        $orders = $shopify->Order->get($filters);
        $paginator = $this->getPaginator($request, $orders);


        //echo '<pre>';
        //var_dump($orders);
        //echo '</pre>';

        return view('order.index')->with('orders', $paginator);
    }
    private function getPaginator(Request $request, $orders)
        {
            $total = count($orders); // total count of the set, this is necessary so the paginator will know the total pages to display
            $page = $request->page ?? 1; // get current page from the request, first page is null
            $perPage = 10; // how many items you want to display per page?
            $offset = ($page - 1) * $perPage; // get the offset, how many items need to be "skipped" on this page
            $orders = array_slice($orders, $offset, $perPage); // the array that we actually pass to the paginator is sliced

            return new LengthAwarePaginator($orders, $total, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query()
            ]);
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
        
        //2- Webservice andreani
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $user = env('ANDREANI_USER');
        $pass = env('ANDREANI_PASS');
        $cliente = env('ANDREANI_CLIENTE');
        $debug = env('ANDREANI_DEBUG');

        $ws = new Andreani($user, $pass, $cliente, $debug);
        
        $contrato = '400006709';

        // Datos de ejemplo obtenidos de https://developers.andreani.com/documentacion/2#crearOrden
        $data = [
            'contrato' => env('ANDREANI_CONTRATO'),
            'origen' => [
                'postal' => [
                    'codigoPostal' => env('ANDREANI_ORIGEN_CP'),
                    'calle' => env('ANDREANI_ORIGEN_CALLE'),
                    'numero' => env('ANDREANI_ORIGEN_NUMERO'),
                    'localidad' => env('ANDREANI_ORIGEN_LOCALIDAD'),
                    'region' => env('ANDREANI_ORIGEN_REGION'),
                    'pais' => 'Argentina',
                    //'componentesDeDireccion' => [
                      //  [
                        //    'meta' => 'entreCalle',
                          //  'contenido' => env('ANDREANI_ORIGEN_ENTRE'),
                        //],
                    //],
                ],
            ],
            'destino' => [
            'postal' => [
                'codigoPostal' => '1292',
                'calle' => 'Macacha Guemes',
                'numero' => '28',
                'localidad' => 'C.A.B.A.',
                'region' => 'AR-B',
                'pais' => 'Argentina',
                'componentesDeDireccion' => [
                [
                    'meta' => 'piso',
                    'contenido' => '2',
                ],
                [
                    'meta' => 'departamento',
                    'contenido' => 'B',
                ],
                ],
            ],
            ],
            'remitente' => [
            'nombreCompleto' => env('ANDREANI_REMITENTE_NOMBRE'),
            'email' => env('ANDREANI_REMITENTE_EMAIL'),
            'documentoTipo' => env('ANDREANI_REMITENTE_DOCTYPE'),
            'documentoNumero' => env('ANDREANI_REMITENTE_DOCNUM'),
            'telefonos' => [
                [
                'tipo' => 1,
                'numero' => env('ANDREANI_REMITENTE_TELEFONO'),
                ],
            ],
            ],
            'destinatario' => [
                [
                    'nombreCompleto' => 'Juana Gonzalez',
                    'email' => 'destinatario@andreani.com',
                    'documentoTipo' => 'DNI',
                    'documentoNumero' => '33999888',
                    'telefonos' => [
                        [
                            'tipo' => 1,
                            'numero' => '1112345678',
                        ],
                    ],
                ],
            ],
            'productoAEntregar' => 'Aire Acondicionado',
            'bultos' => [
                [
                    'kilos' => 2,
                    'largoCm' => 10,
                    'altoCm' => 50,
                    'anchoCm' => 10,
                    'volumenCm' => 5000,
                    'valorDeclaradoSinImpuestos' => 1200,
                    'valorDeclaradoConImpuestos' => 1452,
                    'referencias' => [
                        [
                            'meta' => 'detalle',
                            'contenido' => 'Secador de pelo',
                        ],
                        [
                            'meta' => 'idCliente',
                            'contenido' => '10000',
                        ],
                    ],
                ],
            ],
        ];

        ### 1. Cotizar el envío ###
        // $codigoPostal = $data['destino']['postal']['codigoPostal'];

        // $cotizacion = $ws->cotizarEnvio($codigoPostal, $contrato, $data['bultos']);

        // if (is_null($cotizacion)) {
        //     die('1. (!) No se pudo obtener la Cotización.');
        // }

        //file_put_contents(__DIR__.'/procesoDeEnvio-1-cotizarEnvio.json', json_encode($cotizacion));


        ### 2. Crear la Orden ###
        $orden = $ws->addOrden($data);

        if (is_null($orden)) {
            die('2. (!) No se pudo crear el envio andreani.');
        }
        $date = new DateTime();
        $date = $date->format("y:m:d h:i:s");
        file_put_contents('../storage/app/public/'.$date.'procesoDeEnvio-2-addOrden.json', json_encode($orden));

        // Como este envío es 1 solo bulto obtengo el primer item del array bultos
        $numeroDeEnvio = $orden->bultos[0]->numeroDeEnvio;
        

        ### 3. Obtener la orden ###
        // $orden = $ws->getOrden($numeroDeEnvio);

        // if (is_null($orden)) {
        //     die('3. (!) No se pudo obtener la Orden.');
        // }

        // file_put_contents(__DIR__.'/procesoDeEnvio-3-getOrden.json', json_encode($orden));

        ### 4. Obtener la trazabilidad ###
        //$numeroDeEnvio = $orden->bultos[0]->numeroDeEnvio;
        //$trazabilidad = $ws->getTrazabilidad($numeroDeEnvio);

        // cuando se genera una nueva orden la trazabilidad responde con código 404.
        // if (is_null($trazabilidad)) {
        //     $response = $ws->getResponse();

        //     if ($response->code == 404) {
        //         $trazabilidad = json_decode($response->body);
        //     } else {
        //         die('4. (!) No se pudo Obtener la Trazabilidad.');
        //     }
        // }

        //file_put_contents(__DIR__.'/procesoDeEnvio-4-getTrazatabilidad.json', json_encode($trazabilidad));

        ### 5. Obtener la etiqueta y descargar en browser. ###
        $etiqueta = $ws->getEtiqueta($numeroDeEnvio);

        if (!is_null($etiqueta) && isset($etiqueta->pdf)) { 
            
            file_put_contents('../storage/app/public/'.$numeroDeEnvio.'.pdf', $etiqueta->pdf);
            $destination = '../storage/app/public/'.$numeroDeEnvio.'.pdf';
            $filename = $numeroDeEnvio.'.pdf';
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$filename");
            header("Content-Type: application/pdf");
            header("Content-Transfer-Encoding: binary");
            readfile($destination);
            die('¡Proceso completado OK!');
        }

        die('5. (!) No se pudo obtener la Etiqueta');
        
      
       
            
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
                    "tracking_url" => 'https://www.andreani.com/#!/informacionEnvio/' . $numeroDeEnvio,
                    'tracking_number'=> $numeroDeEnvio,
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
