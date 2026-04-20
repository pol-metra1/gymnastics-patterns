<?php
namespace GymPat;

defined('ABSPATH') || exit;

require_once GYMPAT_PLUGIN_DIR . 'vendor/tcpdf/tcpdf.php';

class PDF_Generator extends \TCPDF {
    private $pattern_data;
    private $user_name;
    private $user_age;
    private $page_overlap = 15; // мм
    private $page_width_mm = 210;
    private $page_height_mm = 297;

    public function __construct($pattern_data, $gymnast_name = '', $gymnast_age = 0) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->pattern_data = $pattern_data;
        $this->user_name = sanitize_text_field($gymnast_name);
        $this->user_age = absint($gymnast_age);

        $this->SetCreator('Gymnastics Pattern Plugin');
        $this->SetAuthor(get_bloginfo('name'));
        $this->SetTitle(sprintf(__('Pattern for %s', 'gymnastics-patterns'), $this->user_name ?: __('Gymnast', 'gymnastics-patterns')));
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
    }

    public function generate($output_path) {
        // Вычисляем масштаб и разбивку
        $bbox = $this->pattern_data['bbox'] ?? ['minX' => 0, 'maxX' => 600, 'minY' => 0, 'maxY' => 1200]; // в мм (условно)
        $drawing_width_mm = $bbox['maxX'] - $bbox['minX'];
        $drawing_height_mm = $bbox['maxY'] - $bbox['minY'];

        // Масштабируем, чтобы вписать в листы с учётом перекрытия
        $effective_width = $this->page_width_mm - 2 * $this->page_overlap;
        $effective_height = $this->page_height_mm - 2 * $this->page_overlap;

        $scale_x = $effective_width / $drawing_width_mm;
        $scale_y = $effective_height / $drawing_height_mm;
        $scale = min($scale_x, $scale_y, 1.0);

        // Определяем количество листов
        $cols = ceil(($drawing_width_mm * $scale) / $effective_width);
        $rows = ceil(($drawing_height_mm * $scale) / $effective_height);

        if ($cols * $rows > get_option('gympat_max_pages', 50)) {
            throw new \Exception(__('Too many pages required.', 'gymnastics-patterns'));
        }

        $total_pages = $cols * $rows;
        $page_num = 1;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $this->AddPage();

                // Персонализация на первом листе или в шапке
                if ($page_num === 1) {
                    $this->SetFont('helvetica', 'B', 14);
                    $label = sprintf(__('Pattern for: %s, %d years', 'gymnastics-patterns'), $this->user_name ?: '—', $this->user_age ?: 0);
                    $this->Text(10, 10, $label);
                }

                // Рисуем метки склейки (кресты)
                $this->draw_crop_marks($col, $row, $cols, $rows);

                // Трансформация для текущего листа
                $offset_x_mm = $col * $effective_width / $scale;
                $offset_y_mm = $row * $effective_height / $scale;

                $this->StartTransform();
                // Масштабирование и смещение
                $this->Scale($scale, $scale);
                $this->Translate(-$offset_x_mm + $this->page_overlap / $scale, -$offset_y_mm + $this->page_overlap / $scale);

                // Рисование деталей выкройки (упрощённо)
                $this->draw_pattern_elements($this->pattern_data);

                $this->StopTransform();

                // Номер листа
                $this->SetFont('helvetica', '', 10);
                $this->Text($this->page_width_mm - 20, $this->page_height_mm - 10, "$page_num / $total_pages");

                $page_num++;
            }
        }

        $this->Output($output_path, 'F');
        return true;
    }

    private function draw_crop_marks($col, $row, $cols, $rows) {
        $cross_size = 5; // мм
        $margin = 10; // мм от края листа

        // Левый верхний угол
        $this->Line($margin, $margin - $cross_size/2, $margin, $margin + $cross_size/2);
        $this->Line($margin - $cross_size/2, $margin, $margin + $cross_size/2, $margin);

        // Правый верхний
        $x = $this->page_width_mm - $margin;
        $this->Line($x, $margin - $cross_size/2, $x, $margin + $cross_size/2);
        $this->Line($x - $cross_size/2, $margin, $x + $cross_size/2, $margin);

        // Левый нижний
        $y = $this->page_height_mm - $margin;
        $this->Line($margin, $y - $cross_size/2, $margin, $y + $cross_size/2);
        $this->Line($margin - $cross_size/2, $y, $margin + $cross_size/2, $y);

        // Правый нижний
        $this->Line($x, $y - $cross_size/2, $x, $y + $cross_size/2);
        $this->Line($x - $cross_size/2, $y, $x + $cross_size/2, $y);

        // Стрелки совмещения между листами
        if ($col > 0) {
            // Стрелка налево
            $this->arrow($margin - 5, $this->page_height_mm/2, $margin, $this->page_height_mm/2);
        }
        if ($col < $cols - 1) {
            // Стрелка направо
            $this->arrow($this->page_width_mm - $margin + 5, $this->page_height_mm/2, $this->page_width_mm - $margin, $this->page_height_mm/2);
        }
        // Аналогично для вертикальных стрелок...
    }

    private function arrow($x1, $y1, $x2, $y2) {
        $this->Line($x1, $y1, $x2, $y2);
        // Рисуем наконечник (упрощённо)
        $this->Line($x2, $y2, $x2-2, $y2-2);
        $this->Line($x2, $y2, $x2-2, $y2+2);
    }

    private function draw_pattern_elements($data) {
        $this->SetLineWidth(0.3);
        // Пример отрисовки линий и кривых
        if (isset($data['grid'])) {
            $this->SetDrawColor(200, 200, 200);
            $g = $data['grid'];
            $this->Line($g['top']['x'], $g['top']['y'], $g['top']['x']+$data['width'], $g['top']['y']);
        }

        // Детали разными цветами
        $this->SetDrawColor(255, 0, 0); // перед красным
        // ... отрисовка по точкам
        $this->SetDrawColor(0, 0, 255); // спинка синим
        // ... и т.д.
    }
}
