<?php
namespace util;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel
{

    /** @var Spreadsheet $excel 电子表格对象 */
    protected $excel;
    // 工作表的Index序列生成器
    protected $sheetIndexSeq = 0;
    // 当前工作表的index
    protected $currentSheetIndex = 0;
    // 文件名
    protected $fileName = '';
    // 列标识
    protected $column;
    // 边框样式
    protected $styleArray;

    /**
     * 初始化数据
     *
     * @param $name
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct($name)
    {
        $this->fileName = $name;
        // 初始化电子表格对象
        $this->excel = new Spreadsheet();
        // 文档属性
        $this->excel->getProperties()->setCreator('')
            ->setLastModifiedBy('')
            ->setTitle('')
            ->setSubject('')
            ->setDescription('')
            ->setKeywords('')
            ->setCategory('');
        // 设置字体
        $this->excel->getDefaultStyle()->getFont()->setName('微软雅黑');
        $this->excel->getDefaultStyle()->getFont()->setSize('10');
        // 边框样式
        $this->styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $this->column = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    }

    /**
     * 设置一个活跃的sheet
     * -- 如果 $index == 0 ，则创建一个新的 sheet
     * -- 如果 $index != 0 ， 则激活一个已存在的 sheet
     *
     * @param $index
     * @param string $name
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setActiveSheet($index = null, $name = '')
    {
        if (is_numeric($index)) {
            $this->currentSheetIndex = $index;
        } else {
            $this->sheetIndexSeq++;
            $this->currentSheetIndex = $this->sheetIndexSeq;
            $this->excel->createSheet();
        }

        // 设置当前活动sheet
        $this->excel->setActiveSheetIndex($this->currentSheetIndex);

        // 设置sheet名称
        $this->excel->getActiveSheet()->setTitle($name);

        // 设置行高
        $this->excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(20);

        // 返回sheetIndex
        return $this->currentSheetIndex;
    }

    /**
     * 设置sheet数据
     *
     * @param $sheetData
     * @param $headData
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function setData($sheetData, $headData)
    {

        // 获取当前活动sheet
        $objActSheet = $this->excel->getActiveSheet();

        // 设置单元格宽度
        if ($headData['width']) {
            foreach ($headData['width'] as $key => $item) {
                $objActSheet->getColumnDimension($key)->setWidth($item);
            }
        }
        // 设置excel表头
        for ($i = 0; $i < count($headData['text']); $i++) {
            $objActSheet->getStyle($this->column[$i] . '1')->applyFromArray($this->styleArray);
            $objActSheet->getCell($this->column[$i] . '1')->setValue($headData['text'][$i]);
        }
        // 循环输出数据
        foreach ($sheetData as $key => $item) {
            for ($j = 0; $j < count($sheetData [0]); $j++) {
                $objActSheet->getStyle($this->column[$j] . ($key + 2))->applyFromArray($this->styleArray);
                $objActSheet->getCell($this->column[$j] . ($key + 2))->setValue($item[$j]);
            }
        }
    }

    /**
     * 导出excel
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export()
    {
        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $this->fileName . '"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($this->excel, 'Xlsx');
        $writer->save('php://output');
        // 释放内存
        $this->excel->disconnectWorksheets();
        unset($this->excel);
        exit;
    }

}