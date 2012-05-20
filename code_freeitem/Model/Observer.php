<?php

class Devopensource_FreeItem_Model_Observer 
{

    public function __construct(){}
            
    // Se ejecuta cuando se ha terminado de añadir el producto
    public function cart_add($observer)
    {
    	try
    	{
	    	$grand_total = Mage::getModel('checkout/cart')->getQuote()->getData('grand_total');

	    	$sku =  Mage::getStoreConfig('freeitem/general/product_sku');
	        $total_purchase = Mage::getStoreConfig('freeitem/general/total_purchase');

	        $product = Mage::getModel('catalog/product');
	        $id = $product->getIdBySku($sku);
	        $product->setStoreId(Mage::app()->getStore()->getId());
	        $product->load($id);
			
			$attrName = Mage::getStoreConfig('freeitem/general/attr_sku');

	        // Si el total del carrito es mayor al configurado en el modulo.
	        if($grand_total>=$total_purchase)
	        {
	                $cart = Mage::getModel('checkout/cart');                
	                $cart->init();
	                $cart_items = $cart->getQuote()->getAllItems();
					
	               foreach($cart_items as $items)
	               {
	               		if($items->getProduct()->getId()==$id)
	               		{
	               			$status = true;
					
                            // hacemos un break, si no lo hacemos siempre tendrá valor false
	               			break;
	               		}else{
	               			$status = false;
	               		}
	               }
	              
	                //Si el producto de regalo no está actualmente en el carrito lo añadimos
	               if(!$status)
	               {
	               		// Comprobamos si es configurable
	               		// TODO: isConfigurable()
						if($product->getTypeId() === 'configurable'){
							
							//cargo el atributo del producto configurable
							$attr = $product->getResource()->getAttribute($attrName);

							//4 es el value del option del dropdown del producto configurable
							//product id producto configurable
							$params = array(
								'product' => $product->getId(),
								'super_attribute' => array(
									$attr->getId() => $product->getData($attrName),
								),
								'qty' => 1,
							);
							
							//cargo el atributo del pproducto configurable
							
							
							//parametros si el prod es un configurable
						

							$cart->addProduct($product, $params);
                        }else{
                                $cart->addProduct($product,array('qty'=>1));
                            //Mage::getSingleton('checkout/session')->setCartWasUpdated(true);	               			               		
                        }
	               
                        // Guardamos el carrito sea configurable o simple
                        $cart->save();

                        // Setteamos el precio a 0
                        $this->setPriceFreeItem($id);
	               }
	        } 
	    }
    	catch(Exception $e)
    	{
    		echo $e;
    	}
    }
    
    // Funcionando con configurable y simple
    public function cart_update($observer)
    {
    	
    	try
    	{
                $grand_total = Mage::getModel('checkout/cart')->getQuote()->getData('grand_total');
                //$sub_total = Mage::getModel('checkout/cart')->getQuote()->getData('subtotal');

                $total_purchase = Mage::getStoreConfig('freeitem/general/total_purchase');

                $sku =  Mage::getStoreConfig('freeitem/general/product_sku');

                $product = Mage::getModel('catalog/product');
                $id = $product->getIdBySku($sku);
                $product->setStoreId(Mage::app()->getStore()->getId());
                $product->load($id);
				
				$attrName = Mage::getStoreConfig('freeitem/general/attr_sku');
				
				
				/**/
				
							
				//Zend_Debug::dump($attrDesign);
				
				/*Zend_Debug::dump($attrDesign->getFrontend()->getValue($product));
				
				Zend_Debug::dump();

				exit();*/

				/**/	
				
	    	if($grand_total >= $total_purchase)
	        {
				
                   $cart = Mage::getModel('checkout/cart');                
	              
	               $cart_items = $cart->getQuote()->getAllItems();

	               foreach($cart_items as $items)
	               {
						//si el id producto actual en el bucle es diferente del prod de regalo
	               		if($items->getProduct()->getId()==$id)
	               		{
                            $status = true;

                            if($items->getQty() > 1){
                                $items->setQty(1);
                            }
                           
                           //hacemos un break ya que si no es el unico prod del carrito status será siempre false
                           break;
	               		}
	               		else
	               		{
	               			$status = false;
	               		}

	               }

				   //Si el producto de regalo no está actualmente en el carrito lo añadimos
	               if(!$status)
	               {
	               		// Comprobamos si es configurable
	               		// TODO: isConfigurable()
						if($product->getTypeId() === 'configurable'){
							
							//cargo el atributo del pproducto configurable
							$attr = $product->getResource()->getAttribute($attrName);
							
							//parametros si el prod es un configurable
							$params = array(
								'product' => $product->getId(),
								'super_attribute' => array(
									$attr->getId() => $product->getData($attrName),
								),
								'qty' => 1,
							);

							$cart->addProduct($product, $params);
                        }else{

                            // añadimos el productio forzando 1 de cantidad
                            $cart->addProduct($product,array('qty'=>1));

                            //Mage::getSingleton('checkout/session')->setCartWasUpdated(true);	               			               		
                        }
                            
                        // Guardamos el carrito sea configurable o simple
                        $cart->save();

                        // Setteamos el precio a 0
                        $this->setPriceFreeItem($id);	                    
	               }
                       

	        }else{
                    $cart = Mage::getModel('checkout/cart');
                    $cart_items = $cart->getQuote()->getAllItems();
                    foreach($cart_items as $items)
                    {
                            if($items->getProduct()->getId()==$id)
                            {
                                $cart->getQuote()->removeItem($id);
                                $cart->getQuote()->isDeleted(true);
                                $cart->save();	     
                                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            }
                    }	        	
           }
    	}
    	catch(Exception $e)
    	{
    		echo $e;
    	}   
	        
    }   
    
   public function cart_delete($observer){
     try
    {
    	//carga del producto a borrar del carrito
        $productRemove = $observer->getQuoteItem()->getProduct();
		
		//cargo precio a restar
        $price = $productRemove->getPrice();

        $sku =  Mage::getStoreConfig('freeitem/general/product_sku');

        $product = Mage::getModel('catalog/product');

        $id = $product->getIdBySku($sku);

        $product->setStoreId(Mage::app()->getStore()->getId());
        $product->load($id);

		// total del carrito actual
        $grand_total = Mage::getModel('checkout/cart')->getQuote()->getData('grand_total');
		
		// total del pedido para que tenga derecho a un regalo
        $total_purchase = Mage::getStoreConfig('freeitem/general/total_purchase');

		//calculamos el total del carrito despues de restar el precio
        $grand_total_update = $grand_total - $price;

        $cart = Mage::getModel('checkout/cart');
        $cart_items = $cart->getQuote()->getAllItems();
			
			//si el carrito es menor a la cantidad de total_purchase y hay mas de
			// un prod. borramos el prod. de regalo
            if ($grand_total_update < $total_purchase && $cart->getItemsCount() > 1) {

                // no cargo el model de cart porque no me borra todo el carrito
                $cartHelper = Mage::helper('checkout/cart');

                $cart_items = $cartHelper->getCart()->getItems();

                foreach($cart_items as $items)
                {

                    if($items->getProduct()->getId()==$id)
                    {
                        $itemId = $items->getItemId();
                        $cartHelper->getCart()->removeItem($itemId)->save();

                        break;
                        // Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                    }
                }	      
            }
    } 	
    	
     catch(Exception $e)
		{
			echo $e;
		}
    	
    }
         
   private function setPriceFreeItem($idProduct){
        $cart = Mage::getModel('checkout/cart');       

        $cart_items = $cart->getQuote()->getAllItems();
        foreach($cart_items as $items)
        {
            //si el id producto actual en el bucle es diferente del prod de regalo
            if($items->getProduct()->getId()==$idProduct)
            {
                $items->setCustomPrice('0');
                $items->setOriginalCustomPrice('0');
                $items->getProduct()->setIsSuperMode(true);

            }
        }
        $cart->save();
    }
}