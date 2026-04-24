<?php
/**
 * PDF Generator Class
 *
 * Генерирует многостраничный PDF с выкройкой в натуральную величину (1:1).
 * Каждая деталь (перед, спинка, рукав, юбка) размещается на отдельных листах A4,
 * при необходимости разбивается на несколько листов с перекрытием 15 мм.
 * Присутствуют метки склейки, заголовки, имя гимнастки, направление долевой нити.
 *
 * @package GymnasticsPatterns
 */

namespace GymPat;

defined('ABSPATH') || exit;

$tcpdf_file = GYMPAT_PLUGIN_DIR . 'vendor/tcpdf/tcpdf.php';
if (file_exists($tcpdf_file)) {
    require_once $tcpdf_file;
}
if (!class_exists('TCPDF')) {
    throw new \Exception('Класс TCPDF не найден. Убедитесь, что библиотека TCPDF находится в vendor/tcpdf/tcpdf.php');
}

class PDF_Generator extends \TCPDF
{
    /** @var array Точки выкройки */
    private $pattern_data;

    /** @var string */
    private $gymnast_name;

    /** @var int */
    private $gymnast_age;

    /** @var string Шрифт с кириллицей */
    private $font_name = 'freesans';

    /** @var float Толщина линий по умолчанию */
    private $line_width = 0.3;

    /** @var float Поля страницы (мм) */
    private $margin = 10;

    /** @var float Перекрытие листов (мм) */
    private $overlap = 15;

    /** @var float Ширина листа A4 (мм) */
    private $page_w = 210;

    /** @var float Высота листа A4 (мм) */
    private $page_h = 297;

    // -------------------------------------------------------------------------
    public function __construct($pattern_data, $gymnast_name = '', $gymnast_age = 0)
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);

        $this->pattern_data = $pattern_data;
        $this->gymnast_name = sanitize_text_field($gymnast_name);
        $this->gymnast_age  = absint($gymnast_age);

        $this->SetCreator('Gymnastics Pattern Plugin');
        $this->SetAuthor(get_bloginfo('name'));
        $title = sprintf(
            __('Выкройка для %s', 'gymnastics-patterns'),
            $this->gymnast_name ?: __('гимнастки', 'gymnastics-patterns')
        );
        $this->SetTitle($title);

        $this->SetMargins($this->margin, $this->margin, $this->margin);
        $this->SetAutoPageBreak(false);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        // Подбор шрифта с кириллицей
        if (!in_array($this->font_name, $this->fontlist, true)) {
            if (in_array('dejavusans', $this->fontlist, true)) {
                $this->font_name = 'dejavusans';
            } elseif (in_array('helvetica', $this->fontlist, true)) {
                $this->font_name = 'helvetica';
            }
        }
        $this->SetFont($this->font_name, '', 10);
    }

    // -------------------------------------------------------------------------
    // Переопределённый метод Arrow (избегаем конфликта с родительским)
    // -------------------------------------------------------------------------
    public function Arrow($x0, $y0, $x1, $y1, $head_style = 0, $arm_size = 5, $arm_angle = 15)
    {
        $this->Line($x0, $y0, $x1, $y1);
        $angle = atan2($y1 - $y0, $x1 - $x0);
        $a1 = $angle + deg2rad($arm_angle);
        $a2 = $angle - deg2rad($arm_angle);
        $this->Line($x1, $y1, $x1 - $arm_size * cos($a1), $y1 - $arm_size * sin($a1));
        $this->Line($x1, $y1, $x1 - $arm_size * cos($a2), $y1 - $arm_size * sin($a2));
    }

    // -------------------------------------------------------------------------
    // Главный метод генерации PDF
    // -------------------------------------------------------------------------
    public function generate($output_path)
    {
        $data = $this->pattern_data;
        if (empty($data)) {
            $this->AddPage();
            $this->Cell(0, 10, 'Нет данных', 0, 1, 'C');
            $this->Output($output_path, 'F');
            return true;
        }

        $scale_mm = 10.0; // 1 см = 10 мм (масштаб 1:1)

        // Перед
        if ($this->hasFront()) {
            $this->drawTiledPart('front', __('Перед', 'gymnastics-patterns'), $scale_mm,
                function ($s, $ox, $oy) {
                    $this->drawFront($s, $ox, $oy);
                }
            );
        }

        // Спинка
        if ($this->hasBack()) {
            $this->drawTiledPart('back', __('Спинка', 'gymnastics-patterns'), $scale_mm,
                function ($s, $ox, $oy) {
                    $this->drawBack($s, $ox, $oy);
                }
            );
        }

        // Рукав (если включён и данные есть)
        if ($this->hasSleeve()) {
            $this->drawTiledPart('sleeve', __('Рукав', 'gymnastics-patterns'), $scale_mm,
                function ($s, $ox, $oy) {
                    $this->drawSleeve($s, $ox, $oy);
                }
            );
        }

        // Юбка
        if ($this->hasSkirt()) {
            $this->drawSkirtTiles($scale_mm);
        }

        // Сохраняем PDF
        $this->Output($output_path, 'F');
        return true;
    }

    // -------------------------------------------------------------------------
    // Проверки наличия частей
    // -------------------------------------------------------------------------
    private function hasFront()
    {
        $d = $this->pattern_data;
        return (isset($d['shoulder']['front_shoulder_tip']) ||
                isset($d['armhole']['front_armhole_curve']));
    }

    private function hasBack()
    {
        $d = $this->pattern_data;
        return (isset($d['shoulder']['back_shoulder_tip']) ||
                isset($d['armhole']['back_armhole_curve']));
    }

    private function hasSleeve()
    {
        return !empty($this->pattern_data['sleeve']) && 
               (!isset($this->pattern_data['has_sleeve']) || $this->pattern_data['has_sleeve']);
    }

    private function hasSkirt()
    {
        $d = $this->pattern_data;
        // Проверяем флаг, если есть
        if (isset($d['has_skirt']) && !$d['has_skirt']) {
            return false;
        }
        // Иначе проверяем наличие любых данных юбки
        return (isset($d['skirt_straight']) || isset($d['skirt_circle']) ||
                isset($d['skirt_gathered']) || isset($d['skirt_tiered']));
    }

    // -------------------------------------------------------------------------
    // Универсальный тайлинг для одной детали
    // -------------------------------------------------------------------------
    private function drawTiledPart($partId, $title, $scale_mm, callable $drawFunc)
    {
        $bbox = $this->getPartBboxMm($partId, $scale_mm);
        if (!$bbox) {
            return;
        }

        $w_mm = $bbox['maxX'] - $bbox['minX'];
        $h_mm = $bbox['maxY'] - $bbox['minY'];

        $pageW_eff = $this->page_w - 2 * $this->margin;
        $pageH_eff = $this->page_h - 2 * $this->margin;

        // Если деталь помещается в один лист
        if ($w_mm <= $pageW_eff && $h_mm <= $pageH_eff) {
            $this->AddPage();
            $this->drawPageHeader($title);
            $ox = $this->margin + ($pageW_eff - $w_mm) / 2 - $bbox['minX'];
            $oy = $this->margin + ($pageH_eff - $h_mm) / 2 - $bbox['minY'];
            $this->SetLineWidth($this->line_width);
            $drawFunc($scale_mm, $ox, $oy);
            $this->drawCropMarks();
        } else {
            // Тайлинг с перекрытием
            $stepX = $pageW_eff - $this->overlap;
            $stepY = $pageH_eff - $this->overlap;
            $cols = (int) ceil(($w_mm - $this->overlap) / $stepX);
            $rows = (int) ceil(($h_mm - $this->overlap) / $stepY);

            for ($row = 0; $row < $rows; $row++) {
                for ($col = 0; $col < $cols; $col++) {
                    $this->AddPage();
                    $this->drawPageHeader($title);
                    $x0 = $bbox['minX'] + $col * $stepX;
                    $y0 = $bbox['minY'] + $row * $stepY;
                    $ox = $this->margin - $x0;
                    $oy = $this->margin - $y0;
                    $this->SetLineWidth($this->line_width);
                    $drawFunc($scale_mm, $ox, $oy);
                    $this->drawCropMarks();
                    $num = $row * $cols + $col + 1;
                    $total = $rows * $cols;
                    $this->SetFont($this->font_name, '', 9);
                    $this->Text($this->page_w - 15, $this->page_h - 5, "$num / $total");
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Bounding box для отдельных частей
    // -------------------------------------------------------------------------
    private function getPartBboxMm($partId, $scale_mm)
    {
        $points = [];
        switch ($partId) {
            case 'front':
                $points = $this->getFrontPoints();
                break;
            case 'back':
                $points = $this->getBackPoints();
                break;
            case 'sleeve':
                $points = $this->getSleevePoints();
                break;
        }
        if (empty($points)) {
            return null;
        }

        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = -PHP_FLOAT_MAX;
        foreach ($points as $p) {
            $x = $p['x'] * $scale_mm;
            $y = $p['y'] * $scale_mm;
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        }
        $minX -= 2; $maxX += 2;
        $minY -= 2; $maxY += 2;
        return compact('minX', 'maxX', 'minY', 'maxY');
    }

    private function getFrontPoints()
    {
        $pts = [];
        $d = $this->pattern_data;
        if (isset($d['shoulder'])) {
            $sh = $d['shoulder'];
            if (isset($sh['front_neck_curve'])) {
                $pts = array_merge($pts, $this->bezierPoints($sh['front_neck_curve']));
            }
            if (isset($sh['front_shoulder_tip'])) $pts[] = $sh['front_shoulder_tip'];
            if (isset($sh['front_neck_base'])) $pts[] = $sh['front_neck_base'];
        }
        if (isset($d['armhole']['front_armhole_curve'])) {
            $pts = array_merge($pts, $this->bezierPoints($d['armhole']['front_armhole_curve']));
        }
        if (isset($d['panties'])) {
            $panties = $d['panties'];
            if (isset($panties['leg_front_curve'])) {
                $pts = array_merge($pts, $this->bezierPoints($panties['leg_front_curve']));
            }
            if (isset($panties['front_waist'])) $pts[] = $panties['front_waist'];
            if (isset($panties['side_waist'])) $pts[] = $panties['side_waist'];
            if (isset($panties['crotch_front'])) $pts[] = $panties['crotch_front'];
        }
        return $this->filterPoints($pts);
    }

    private function getBackPoints()
    {
        $pts = [];
        $d = $this->pattern_data;
        if (isset($d['shoulder'])) {
            $sh = $d['shoulder'];
            if (isset($sh['back_neck_curve'])) {
                $pts = array_merge($pts, $this->bezierPoints($sh['back_neck_curve']));
            }
            if (isset($sh['back_shoulder_tip'])) $pts[] = $sh['back_shoulder_tip'];
            if (isset($sh['back_neck_base'])) $pts[] = $sh['back_neck_base'];
        }
        if (isset($d['armhole']['back_armhole_curve'])) {
            $pts = array_merge($pts, $this->bezierPoints($d['armhole']['back_armhole_curve']));
        }
        if (isset($d['panties'])) {
            $panties = $d['panties'];
            if (isset($panties['leg_back_curve'])) {
                $pts = array_merge($pts, $this->bezierPoints($panties['leg_back_curve']));
            }
            if (isset($panties['back_waist'])) $pts[] = $panties['back_waist'];
            if (isset($panties['side_waist'])) $pts[] = $panties['side_waist'];
            if (isset($panties['crotch_back'])) $pts[] = $panties['crotch_back'];
        }
        return $this->filterPoints($pts);
    }

    private function getSleevePoints()
    {
        if (!isset($this->pattern_data['sleeve'])) return [];
        $s = $this->pattern_data['sleeve'];
        $pts = [
            $s['underarm_left'] ?? null,
            $s['underarm_right'] ?? null,
            $s['hem_left'] ?? null,
            $s['hem_right'] ?? null,
            $s['top_center'] ?? null,
        ];
        if (isset($s['sleeve_cap_curve'])) {
            $pts = array_merge($pts, $this->bezierPoints($s['sleeve_cap_curve']));
        }
        return $this->filterPoints($pts);
    }

    private function bezierPoints($curve)
    {
        if (!$curve) return [];
        $pt = [];
        foreach (['start', 'cp1', 'cp2', 'end'] as $key) {
            if (isset($curve[$key]) && is_array($curve[$key]) && isset($curve[$key]['x'], $curve[$key]['y']))
                $pt[] = $curve[$key];
        }
        return $pt;
    }

    private function filterPoints(array $pts)
    {
        return array_values(array_filter($pts, function ($p) {
            return is_array($p) && isset($p['x'], $p['y']) && is_numeric($p['x']) && is_numeric($p['y']);
        }));
    }

    // -------------------------------------------------------------------------
    // Отрисовка конкретных деталей
    // -------------------------------------------------------------------------
    private function drawFront($scale_mm, $ox, $oy)
    {
        $d = $this->pattern_data;
        $this->SetDrawColor(255, 0, 0); // красный

        if (isset($d['shoulder'])) {
            $sh = $d['shoulder'];
            // горловина (кривая)
            if (isset($sh['front_neck_curve'])) {
                $this->drawBezier($sh['front_neck_curve'], $scale_mm, $ox, $oy);
            }
            // плечо
            if (isset($sh['front_neck_base'], $sh['front_shoulder_tip'])) {
                $this->Line(
                    $sh['front_neck_base']['x'] * $scale_mm + $ox,
                    $sh['front_neck_base']['y'] * $scale_mm + $oy,
                    $sh['front_shoulder_tip']['x'] * $scale_mm + $ox,
                    $sh['front_shoulder_tip']['y'] * $scale_mm + $oy
                );
            }
        }

        // Пройма (кривая)
        if (isset($d['armhole']['front_armhole_curve'])) {
            $this->drawBezier($d['armhole']['front_armhole_curve'], $scale_mm, $ox, $oy);
        }

        // Трусы (передняя половинка)
        if (isset($d['panties'])) {
            $p = $d['panties'];
            // Линия талии переда
            if (isset($p['front_waist'], $p['side_waist'])) {
                $this->Line(
                    $p['front_waist']['x'] * $scale_mm + $ox,
                    $p['front_waist']['y'] * $scale_mm + $oy,
                    $p['side_waist']['x'] * $scale_mm + $ox,
                    $p['side_waist']['y'] * $scale_mm + $oy
                );
            }
            // Боковой шов до трусов (упрощённо)
            if (isset($p['side_waist'], $p['leg_front_curve'])) {
                // Начало кривой ноги
                $this->Line(
                    $p['side_waist']['x'] * $scale_mm + $ox,
                    $p['side_waist']['y'] * $scale_mm + $oy,
                    $p['leg_front_curve']['start']['x'] * $scale_mm + $ox,
                    $p['leg_front_curve']['start']['y'] * $scale_mm + $oy
                );
            }
            // Кривая выреза ноги (перед)
            if (isset($p['leg_front_curve'])) {
                $this->drawBezier($p['leg_front_curve'], $scale_mm, $ox, $oy);
            }
        }
    }

    private function drawBack($scale_mm, $ox, $oy)
    {
        $d = $this->pattern_data;
        $this->SetDrawColor(0, 0, 255); // синий

        if (isset($d['shoulder'])) {
            $sh = $d['shoulder'];
            if (isset($sh['back_neck_curve'])) {
                $this->drawBezier($sh['back_neck_curve'], $scale_mm, $ox, $oy);
            }
            if (isset($sh['back_neck_base'], $sh['back_shoulder_tip'])) {
                $this->Line(
                    $sh['back_neck_base']['x'] * $scale_mm + $ox,
                    $sh['back_neck_base']['y'] * $scale_mm + $oy,
                    $sh['back_shoulder_tip']['x'] * $scale_mm + $ox,
                    $sh['back_shoulder_tip']['y'] * $scale_mm + $oy
                );
            }
        }

        if (isset($d['armhole']['back_armhole_curve'])) {
            $this->drawBezier($d['armhole']['back_armhole_curve'], $scale_mm, $ox, $oy);
        }

        if (isset($d['panties'])) {
            $p = $d['panties'];
            // Задняя талия
            if (isset($p['back_waist'], $p['side_waist'])) {
                $this->Line(
                    $p['back_waist']['x'] * $scale_mm + $ox,
                    $p['back_waist']['y'] * $scale_mm + $oy,
                    $p['side_waist']['x'] * $scale_mm + $ox,
                    $p['side_waist']['y'] * $scale_mm + $oy
                );
            }
            // Боковой шов до трусов
            if (isset($p['side_waist'], $p['leg_back_curve'])) {
                $this->Line(
                    $p['side_waist']['x'] * $scale_mm + $ox,
                    $p['side_waist']['y'] * $scale_mm + $oy,
                    $p['leg_back_curve']['start']['x'] * $scale_mm + $ox,
                    $p['leg_back_curve']['start']['y'] * $scale_mm + $oy
                );
            }
            if (isset($p['leg_back_curve'])) {
                $this->drawBezier($p['leg_back_curve'], $scale_mm, $ox, $oy);
            }
        }
    }

    private function drawSleeve($scale_mm, $ox, $oy)
    {
        if (!isset($this->pattern_data['sleeve'])) return;
        $s = $this->pattern_data['sleeve'];
        $this->SetDrawColor(0, 128, 0); // зелёный

        // Окат рукава (кривая)
        if (isset($s['sleeve_cap_curve'])) {
            $this->drawBezier($s['sleeve_cap_curve'], $scale_mm, $ox, $oy);
        }

        // Боковые линии
        $lx = $s['underarm_left']['x'] * $scale_mm + $ox;
        $ly_top = $s['underarm_left']['y'] * $scale_mm + $oy;
        $ly_bot = $s['hem_left']['y'] * $scale_mm + $oy;

        $rx = $s['underarm_right']['x'] * $scale_mm + $ox;
        $ry_top = $s['underarm_right']['y'] * $scale_mm + $oy;
        $ry_bot = $s['hem_right']['y'] * $scale_mm + $oy;

        $this->Line($lx, $ly_top, $lx, $ly_bot);
        $this->Line($rx, $ry_top, $rx, $ry_bot);

        // Низ рукава
        $this->Line($lx, $ly_bot, $rx, $ry_bot);

        // Направление долевой нити (стрелка по центру)
        $cx = ($lx + $rx) / 2;
        $this->Arrow($cx, $ly_bot + 10, $cx, $ly_top - 10);
    }

    // -------------------------------------------------------------------------
    // Юбка
    // -------------------------------------------------------------------------
    private function drawSkirtTiles($scale_mm)
    {
        $data = $this->pattern_data;

        if (isset($data['skirt_straight'])) {
            $type = 'straight';
            $skirtData = $data['skirt_straight'];
            $title = __('Прямая юбка', 'gymnastics-patterns');
        } elseif (isset($data['skirt_circle'])) {
            $type = 'circle';
            $skirtData = $data['skirt_circle'];
            $title = __('Юбка-солнце', 'gymnastics-patterns');
        } elseif (isset($data['skirt_gathered'])) {
            $type = 'gathered';
            $skirtData = $data['skirt_gathered'];
            $title = __('Юбка-татьянка', 'gymnastics-patterns');
        } elseif (isset($data['skirt_tiered'])) {
            $type = 'tiered';
            $skirtData = $data['skirt_tiered'];
            $title = __('Ярусная юбка', 'gymnastics-patterns');
        } else {
            return;
        }

        // Для солнца с сегментами отдельная обработка
        if ($type === 'circle' && isset($skirtData['segments']) && $skirtData['segments'] > 1) {
            $this->drawCircleSkirtSegments($skirtData, $title, $scale_mm);
            return;
        }

        // Остальные типы – тайлинг по bbox
        $bbox = $this->getSkirtBboxMm($type, $skirtData, $scale_mm);
        if (!$bbox) return;

        $w_mm = $bbox['maxX'] - $bbox['minX'];
        $h_mm = $bbox['maxY'] - $bbox['minY'];
        $pageW_eff = $this->page_w - 2 * $this->margin;
        $pageH_eff = $this->page_h - 2 * $this->margin;

        if ($w_mm <= $pageW_eff && $h_mm <= $pageH_eff) {
            $this->AddPage();
            $this->drawPageHeader($title);
            $ox = $this->margin + ($pageW_eff - $w_mm) / 2 - $bbox['minX'];
            $oy = $this->margin + ($pageH_eff - $h_mm) / 2 - $bbox['minY'];
            $this->SetLineWidth($this->line_width);
            $this->drawSkirtByType($type, $skirtData, $scale_mm, $ox, $oy);
            $this->drawCropMarks();
        } else {
            $stepX = $pageW_eff - $this->overlap;
            $stepY = $pageH_eff - $this->overlap;
            $cols = (int) ceil(($w_mm - $this->overlap) / $stepX);
            $rows = (int) ceil(($h_mm - $this->overlap) / $stepY);
            for ($row = 0; $row < $rows; $row++) {
                for ($col = 0; $col < $cols; $col++) {
                    $this->AddPage();
                    $this->drawPageHeader($title);
                    $x0 = $bbox['minX'] + $col * $stepX;
                    $y0 = $bbox['minY'] + $row * $stepY;
                    $ox = $this->margin - $x0;
                    $oy = $this->margin - $y0;
                    $this->SetLineWidth($this->line_width);
                    $this->drawSkirtByType($type, $skirtData, $scale_mm, $ox, $oy);
                    $this->drawCropMarks();
                    $num = $row * $cols + $col + 1;
                    $total = $rows * $cols;
                    $this->SetFont($this->font_name, '', 9);
                    $this->Text($this->page_w - 15, $this->page_h - 5, "$num / $total");
                }
            }
        }
    }

    private function getSkirtBboxMm($type, $data, $scale_mm)
    {
        switch ($type) {
            case 'straight':
                $fp = $data['front_panel'];
                $bp = $data['back_panel'];
                $totalW = ($fp['waist_width'] + $bp['waist_width'] + 3) * $scale_mm; // 3 см зазор между полотнищами
                $totalH = max($fp['length'], $bp['length']) * $scale_mm;
                return ['minX' => 0, 'maxX' => $totalW, 'minY' => 0, 'maxY' => $totalH];

            case 'circle':
                $R = $data['radius_hem'] * $scale_mm;
                // Окружность радиусом R, центр в (0,0)
                return ['minX' => -$R, 'maxX' => $R, 'minY' => -$R, 'maxY' => $R];

            case 'half_circle':
                $R = $data['radius_hem'] * $scale_mm;
                // Полукруг: X от -R до R, Y от 0 до R
                return ['minX' => -$R, 'maxX' => $R, 'minY' => 0, 'maxY' => $R];

            case 'gathered':
                $w = $data['panel_width'] * $scale_mm;
                $h = $data['length'] * $scale_mm;
                return ['minX' => 0, 'maxX' => $w, 'minY' => 0, 'maxY' => $h];

            case 'tiered':
                $maxW = 0;
                $totalH = 0;
                foreach ($data['tiers'] as $tier) {
                    $maxW = max($maxW, $tier['width']);
                    $totalH += $tier['height'];
                }
                $w = $maxW * $scale_mm;
                $h = $totalH * $scale_mm;
                return ['minX' => 0, 'maxX' => $w, 'minY' => 0, 'maxY' => $h];
        }
        return null;
    }

    private function drawSkirtByType($type, $data, $scale_mm, $ox, $oy)
    {
        switch ($type) {
            case 'straight':
                $this->drawStraightSkirt($data, $scale_mm, $ox, $oy);
                break;
            case 'circle':
                $this->drawCircleSkirt($data, $scale_mm, $ox, $oy, 360);
                break;
            case 'half_circle':
                $this->drawCircleSkirt($data, $scale_mm, $ox, $oy, 180);
                break;
            case 'gathered':
                $this->drawGatheredSkirt($data, $scale_mm, $ox, $oy);
                break;
            case 'tiered':
                $this->drawTieredSkirt($data, $scale_mm, $ox, $oy);
                break;
        }
    }

    private function drawStraightSkirt($data, $scale_mm, $ox, $oy)
    {
        $front = $data['front_panel'];
        $back  = $data['back_panel'];
        $this->SetDrawColor(255, 0, 0);
        $this->rectangle(0, 0, $front['waist_width'], $front['length'], $scale_mm, $ox, $oy);
        $back_x = $front['waist_width'] + 3;
        $this->SetDrawColor(0, 0, 255);
        $this->rectangle($back_x, 0, $back['waist_width'], $back['length'], $scale_mm, $ox, $oy);
    }

    private function drawCircleSkirt($data, $scale_mm, $ox, $oy, $full_angle = 360)
    {
        $this->SetDrawColor(0, 128, 0);
        $Rw = $data['radius_waist'] * $scale_mm;
        $Rh = $data['radius_hem'] * $scale_mm;
        // Центр в (0, 0) + смещение
        $cx = 0 + $ox;
        $cy = 0 + $oy;

        if ($full_angle == 360) {
            $this->Circle($cx, $cy, $Rw);
            $this->Circle($cx, $cy, $Rh);
        } else {
            // Полусолнце: сектор от -90 до 90 градусов
            $start = -$full_angle / 2;
            $end = $full_angle / 2;
            $this->Arc($cx, $cy, $Rw, $Rw, $start, $end);
            $this->Arc($cx, $cy, $Rh, $Rh, $start, $end);
            // Соединяем концы радиусов
            $rad_start = deg2rad($start);
            $rad_end   = deg2rad($end);
            $this->Line(
                $cx + $Rw * cos($rad_start), $cy + $Rw * sin($rad_start),
                $cx + $Rh * cos($rad_start), $cy + $Rh * sin($rad_start)
            );
            $this->Line(
                $cx + $Rw * cos($rad_end), $cy + $Rw * sin($rad_end),
                $cx + $Rh * cos($rad_end), $cy + $Rh * sin($rad_end)
            );
        }
        // Направление долевой нити
        $this->SetDrawColor(100, 100, 100);
        $this->Arrow($cx, $cy - $Rh - 5, $cx, $cy - $Rw + 5);
    }

    private function drawCircleSkirtSegments($data, $title, $scale_mm)
    {
        $segments = $data['segments'];
        for ($i = 0; $i < $segments; $i++) {
            $this->AddPage();
            $this->drawPageHeader($title . ' ' . sprintf(__('Сегмент %d из %d', 'gymnastics-patterns'), $i + 1, $segments));
            $angle = 360 / $segments;
            $start = $i * $angle - 90;
            $end = $start + $angle;
            $cx = $this->page_w / 2;
            $cy = $this->page_h / 2;
            $this->SetLineWidth($this->line_width);
            $this->SetDrawColor(0, 128, 0);
            $Rw = $data['radius_waist'] * $scale_mm;
            $Rh = $data['radius_hem'] * $scale_mm;
            $this->Arc($cx, $cy, $Rw, $Rw, $start, $end);
            $this->Arc($cx, $cy, $Rh, $Rh, $start, $end);
            $rad_start = deg2rad($start);
            $rad_end   = deg2rad($end);
            $this->Line(
                $cx + $Rw * cos($rad_start), $cy + $Rw * sin($rad_start),
                $cx + $Rh * cos($rad_start), $cy + $Rh * sin($rad_start)
            );
            $this->Line(
                $cx + $Rw * cos($rad_end), $cy + $Rw * sin($rad_end),
                $cx + $Rh * cos($rad_end), $cy + $Rh * sin($rad_end)
            );
            $this->drawCropMarks();
        }
    }

    private function drawGatheredSkirt($data, $scale_mm, $ox, $oy)
    {
        $this->SetDrawColor(128, 0, 128);
        $this->rectangle(0, 0, $data['panel_width'], $data['length'], $scale_mm, $ox, $oy);
    }

    private function drawTieredSkirt($data, $scale_mm, $ox, $oy)
    {
        $this->SetDrawColor(128, 0, 128);
        $y = 0;
        foreach ($data['tiers'] as $tier) {
            $this->rectangle(0, $y, $tier['width'], $tier['height'], $scale_mm, $ox, $oy);
            $y += $tier['height'];
        }
    }

    // -------------------------------------------------------------------------
    // Вспомогательные методы
    // -------------------------------------------------------------------------
    private function drawBezier($curve, $scale_mm, $ox, $oy)
    {
        if (!isset($curve['start'], $curve['cp1'], $curve['cp2'], $curve['end'])) return;
        $this->Curve(
            $curve['start']['x'] * $scale_mm + $ox,
            $curve['start']['y'] * $scale_mm + $oy,
            $curve['cp1']['x']   * $scale_mm + $ox,
            $curve['cp1']['y']   * $scale_mm + $oy,
            $curve['cp2']['x']   * $scale_mm + $ox,
            $curve['cp2']['y']   * $scale_mm + $oy,
            $curve['end']['x']   * $scale_mm + $ox,
            $curve['end']['y']   * $scale_mm + $oy
        );
    }

    private function drawCropMarks()
    {
        $c = 5;
        $m = $this->margin;
        $w = $this->getPageWidth();
        $h = $this->getPageHeight();
        $this->SetDrawColor(0, 0, 0);
        $this->cross($m, $m, $c);
        $this->cross($w - $m, $m, $c);
        $this->cross($m, $h - $m, $c);
        $this->cross($w - $m, $h - $m, $c);
    }

    private function cross($x, $y, $size)
    {
        $this->Line($x, $y - $size / 2, $x, $y + $size / 2);
        $this->Line($x - $size / 2, $y, $x + $size / 2, $y);
    }

    private function drawPageHeader($title)
    {
        $this->SetFont($this->font_name, 'B', 14);
        $this->SetXY($this->margin, 5);
        $this->Cell(0, 10, $title, 0, 1, 'C');
        $this->SetFont($this->font_name, '', 10);
        $label = sprintf(__('Выкройка для: %s, %d лет', 'gymnastics-patterns'), $this->gymnast_name ?: '—', $this->gymnast_age ?: 0);
        $this->SetXY($this->margin, 13);
        $this->Cell(0, 6, $label, 0, 1, 'L');
    }

    private function rectangle($x, $y, $w, $h, $scale_mm, $ox, $oy)
    {
        $x = $x * $scale_mm + $ox;
        $y = $y * $scale_mm + $oy;
        $w = $w * $scale_mm;
        $h = $h * $scale_mm;
        $this->Line($x, $y, $x + $w, $y);
        $this->Line($x + $w, $y, $x + $w, $y + $h);
        $this->Line($x + $w, $y + $h, $x, $y + $h);
        $this->Line($x, $y + $h, $x, $y);
    }
}
