<?php
 namespace infrastructure\gd\slide\cards\slide_1\v1;
 
 use infrastructure\v1\Infrastructure;
 

 class SlideGenerator extends Infrastructure {
 
     public function run($rq) {
 
         $jsonconfigstr = file_get_contents($this->configPath);
         $jsonconfig = json_decode($jsonconfigstr,true);
         $templatesPath = $jsonconfig["templates_path"];
         $productImagesPath = $jsonconfig["product_images_path"];
 
         // Проверяем, что передан артикула товара, для которого будет сгенерированао изображение
         if (!@$rq['plu']) throw new \Exception("Не указан артикула товара для генерации изображения");
         if (!@$rq['cut']) throw new \Exception("Не указан вид выреза товара для генерации изображения");
 
         /*
             Алгоритм действий:
             - создаём изображение из картинки продукта
             - создаём изображение из шаблона
             - накладываем шаблон сверху на картинку продукта
             - в результирующей картинке делаем прозрачным белым цвет
         */
 
         // Точка вставки карты (продукта) в фоновое изображение шаблона
         $startX = 25;
         $startY = 600;
         // Размеры карты на фоновом изображении
         // k = 1.57
         $widthX = 1750;
         // 1046
         $widthY = $widthX / 1.57;
 
         ini_set('memory_limit', '256M');
 
         // Создаём изображение из картинки продукта
         $resultingImagePath = $productImagesPath.'/'.$rq['plu'].'/'.$rq['plu'].'_'.$rq['cut'].'_slide_1.png';
 
         // изображение флага
         $productImageName = $productImagesPath.'/'.$rq['plu'].'/'.$rq['plu'].'_'.$rq['cut'].'.png';
         if (!file_exists($productImageName)) throw new \Exception("Файл с изображением продукта не найден");
         $productImage = imagecreatefrompng($productImageName);
         list($width, $height, $type, $attr) = getimagesize($productImageName);
 
         $resultingImage1 = imagecreatetruecolor($width, $height);
         imagesavealpha($resultingImage1, true);
         $trans_colour = imagecolorallocatealpha($resultingImage1, 0, 0, 0, 127);
         imagefill($resultingImage1, 0, 0, $trans_colour);
 
         imagecopy($resultingImage1,$productImage,0,0,0,0,$width,$height);
 
         
         // фоновое изображение
         $templateName = $templatesPath.'/card/slide_1.png';
         if (!file_exists($templateName)) throw new \Exception("Файл с изображением слайда не найден");
         $templateImage = imagecreatefrompng($templateName);
         $widthTemplate = imagesx($templateImage);
         $heightTemplate = imagesy($templateImage);
 
         $resultingImage = imagecreatetruecolor($widthTemplate, $heightTemplate);
         
         imagecopy($resultingImage,$templateImage,0,0,0,0,$widthTemplate,$heightTemplate);
 
         
         $compressedProductImage = imagecreatetruecolor($widthX, $widthY);
         imagesavealpha($compressedProductImage, true);
         $trans_colour = imagecolorallocatealpha($compressedProductImage, 0, 0, 0, 127);
         imagefill($compressedProductImage, 0, 0, $trans_colour);
 
         imagecopyresampled($compressedProductImage, $resultingImage1, 0, 0, 0, 0, $widthX, $widthY, $width, $height);
 
         $width = imagesx($compressedProductImage);
         $height = imagesy($compressedProductImage);
 
         imagecopy($resultingImage,$compressedProductImage,$startX,$startY,0,0,$width,$height);
         imagealphablending($resultingImage, false);
         imagesavealpha($resultingImage, true);
 
         // Временно выводим изображение на экран
         header('Content-Type: image/png');
         imagepng($resultingImage);
 
         // Пишем результат генерации слайда в файл и выводим на экран
         $res = imagepng($resultingImage,$resultingImagePath);
         if (!$res) throw new \Exception('Ошибка записи результирующего изображения в файл');
 
         imagedestroy($productImage);
         imagedestroy($templateImage);
         imagedestroy($resultingImage);
         imagedestroy($compressedProductImage);
     } // run
 
 } // class
?>