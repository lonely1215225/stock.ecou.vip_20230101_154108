<?php
namespace util;

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportExcel
{

    public static function excel($list, $fileName, $style,$index)
    {
        try {
            $helper = new Sample();
            if ($helper->isCli()) {
                $helper->log('This example should only be run from a Web Browser' . PHP_EOL);
                return;
            }
            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->getProperties()->setCreator('Maarten Balliauw')
                ->setLastModifiedBy('Maarten Balliauw')
                ->setTitle('Office 2007 XLSX Test Document')
                ->setSubject('Office 2007 XLSX Test Document')
                ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
                ->setKeywords('office 2007 openxml php')
                ->setCategory('Test result file');
            $objActSheet = $objPHPExcel->getActiveSheet();
            if ($style) {
                foreach ($style as $key => $item) {
                    $objActSheet->getColumnDimension($key)->setWidth($item);
                    $index++;
                }
                $objPHPExcel->getActiveSheet()->getStyle('A1:J' . $index)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            if (!empty($list)) {
                $ary      = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
                $cellname = "";
                $nameary  = array();
                $keyary   = array();
                $k        = 0;
                for ($i = 0; $i < count($list[0]); $i++) {
                    $str = "";
                    if ($i % 26 == 0) {
                        $k = 0;
                    }
                    $cellname = $ary [$k];
                    if (ceil(($i + 1) / 26) > 1) {
                        for ($j = 0; $j < ceil(($i + 1) / 26); $j++) {
                            $str .= $cellname;
                        }
                        $nameary [] = $str;
                    } else {
                        $nameary [] = $cellname;
                    }
                    $k++;
                }
                $i = 0;
                foreach ($list[0] as $key => $iteam) {
                    $keyary[] = $key;
                    $objActSheet->getCell($nameary [$i] . "1")->setValue($key);
                    $i++;
                }
                foreach ($list as $key => $iteam) {
                    for ($j = 0; $j < count($list [0]); $j++) {
                        $objActSheet->getCell($nameary [$j] . ($key + 2))->setValue($iteam [$keyary[$j]]);
                    }
                }
            }
            $type = pathinfo($fileName, PATHINFO_EXTENSION);
            if($type == 'xlsx'){
                // Redirect output to a client’s web browser (Xlsx)
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $writerType = 'Xlsx';
            }else{
                // Redirect output to a client’s web browser (Xls)
                header('Content-Type: application/vnd.ms-excel');
                $writerType = 'Xls';
            }
            //输出名称
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $writer = IOFactory::createWriter($objPHPExcel, $writerType);
            $writer->save('php://output');
            // return null;
        }catch (\Exception $e) {
            dump($e->getFile());
            dump($e->getLine());
            dump($e->getMessage());
            dump($e->getTraceAsString());
        }
    }

    /**
     * 导出Excel
     * @param  object $spreadsheet  数据
     * @param  string $format       格式:excel2003 = xls, excel2007 = xlsx
     * @param  string $savename     保存的文件名
     * @return filedownload         浏览器下载
     */
    public static function exportExcel($spreadsheet, $savename) {
        try{
            if (!$spreadsheet) return false;
            $format = pathinfo($savename,PATHINFO_EXTENSION);
            if ($format == 'xls') {
                //输出Excel03版本
                header('Content-Type:application/vnd.ms-excel');
                $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
            } elseif ($format == 'xlsx') {
                //输出07Excel版本
                //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Type:application/vnd.ms-excel');
                $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
            }
            //输出名称
            header('Content-Disposition: attachment;filename="'.$savename.'"');
            //禁止缓存
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $writer = new $class($spreadsheet);
            $filePath = env('runtime_path')."temp/".time().microtime(true).".tmp";
            $writer->save($filePath);
            readfile($filePath);
            unlink($filePath);
        }catch (\Exception $e){
            dump($e->getFile());
            dump($e->getLine());
            dump($e->getMessage());
            dump($e->getTraceAsString());
        }

    }

}
