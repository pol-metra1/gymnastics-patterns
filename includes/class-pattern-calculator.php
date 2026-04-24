<?php
/**
 * Pattern Calculator Class
 *
 * Выполняет все построения выкройки купальника и юбки на основе
 * адаптированных методик ЕМКО СЭВ и Мюллера.
 * Все координаты возвращаются в сантиметрах.
 *
 * @package GymnasticsPatterns
 * @since   1.0.0
 */

namespace GymPat;

defined( 'ABSPATH' ) || exit;

class PatternCalculator {

    /**
     * Очищенные параметры мерок.
     *
     * @var array
     */
    private $params;

    /**
     * Рассчитанные точки и кривые.
     *
     * @var array
     */
    private $points = [];

    /**
     * Прибавки на свободу облегания (см).
     */
    const EASE_BUST       = 2.0;
    const EASE_WAIST      = 1.0;
    const EASE_HIPS       = 1.5;
    const EASE_ARMHOLE    = 3.0;
    const EASE_SLEEVE_CAP = 1.5;

    /**
     * Конструктор.
     *
     * @param array $params Входные параметры.
     */
    public function __construct( array $params ) {
        $this->params = $this->sanitize_params( $params );
        $this->calculate_grid();
    }

    /**
     * Очистка и установка значений по умолчанию.
     *
     * @param array $params
     * @return array
     */
    private function sanitize_params( $params ) {
        $defaults = [
            // Персональные
            'gymnast_name'          => '',
            'gymnast_age'           => 0,

            // Основные мерки
            'height'                => 160.0,
            'bust'                  => 80.0,
            'under_bust'            => 70.0,
            'waist'                 => 65.0,
            'hips'                  => 90.0,
            'chest_width'           => 30.0,
            'back_width'            => 32.0,
            'front_waist_length'    => 40.0,
            'back_waist_length'     => 38.0,
            'bust_height'           => 25.0,
            'bust_distance'         => 18.0,
            'armhole_depth'         => 18.0,
            'arm_circ'              => 28.0,
            'shoulder_length'       => 12.0,
            'sleeve_length'         => 25.0,
            'wrist_circ'            => 16.0,
            'seat_height'           => 28.0,
            'side_length'           => 15.0,
            'leg_circ'              => 55.0,
            'step_width'            => 8.0,
            'leotard_length'        => 70.0,
            'neck_circ'             => 35.0,
            'front_neck_width'      => 10.0,
            'front_neck_depth'      => 5.0,
            'back_neck_width'       => 12.0,
            'back_neck_depth'       => 2.0,
            'side_seam_length'      => 18.0,
            'front_waist_circ'      => 35.0,
            'back_waist_circ'       => 30.0,
            'bust_fullness'         => 3,

            // Рукав
            'has_sleeve'            => true,

            // Юбка
            'has_skirt'             => false,
            'skirt_type'            => 'straight',
            'skirt_length'          => 40.0,
            'flare_coefficient'     => 1.2,
            'tiers'                 => 2,
            'yoke_height'           => 0.0,
            'skirt_waist'           => null,
            'skirt_hips'            => null,
        ];

        $sanitized = [];
        foreach ( $defaults as $key => $def ) {
            $val = $params[ $key ] ?? $def;
            if ( is_numeric( $def ) ) {
                $sanitized[ $key ] = floatval( $val );
            } elseif ( is_bool( $def ) ) {
                $sanitized[ $key ] = (bool) $val;
            } else {
                $sanitized[ $key ] = sanitize_text_field( (string) $val );
            }
        }

        // Мерки для юбки (если не заданы отдельно)
        if ( empty( $sanitized['skirt_waist'] ) ) {
            $sanitized['skirt_waist'] = $sanitized['waist'];
        }
        if ( empty( $sanitized['skirt_hips'] ) ) {
            $sanitized['skirt_hips'] = $sanitized['hips'];
        }

        return $sanitized;
    }

    /**
     * Построение базисной сетки.
     * Оси координат: X – слева направо, Y – сверху вниз.
     */
    private function calculate_grid() {
        $bust  = $this->params['bust']  + self::EASE_BUST;
        $waist = $this->params['waist'] + self::EASE_WAIST;
        $hips  = $this->params['hips']  + self::EASE_HIPS;

        // Вертикальные отметки (Y)
        $y_shoulder    = 0;
        $y_bust        = $this->params['armhole_depth'] + 1.5;   // линия груди
        $y_under_bust  = $y_bust + $this->params['bust_height'] * 0.5;
        $y_waist       = $this->params['back_waist_length'];
        $y_hips        = $y_waist + 20;                           // стандартное расстояние
        $y_crotch      = $this->params['leotard_length'];
        $y_hem         = $y_crotch + 2;

        // Ширина чертежа: полуобхват груди + прибавки
        $half_bust = $bust / 2 + 2.0;  // 2 см на свободное облегание и швы

        // Центры
        $center_front = 0;
        $center_back  = $half_bust;

        $this->points['grid'] = [
            'center_front'  => $center_front,
            'center_back'   => $center_back,
            'block_width'   => $half_bust,
            'y_shoulder'    => $y_shoulder,
            'y_bust'        => $y_bust,
            'y_under_bust'  => $y_under_bust,
            'y_waist'       => $y_waist,
            'y_hips'        => $y_hips,
            'y_crotch'      => $y_crotch,
            'y_hem'         => $y_hem,
        ];
    }

    /**
     * Плечевые срезы и горловины.
     */
    public function calculate_shoulder() {
        $grid = $this->points['grid'];

        // Горловина переда
        $fnw = $this->params['front_neck_width'];
        $fnd = $this->params['front_neck_depth'];
        $bnw = $this->params['back_neck_width'];
        $bnd = $this->params['back_neck_depth'];

        // Перед
        $cf_top = [ 'x' => $grid['center_front'], 'y' => $grid['y_shoulder'] ];
        $fn_base = [ 'x' => $cf_top['x'] + $fnw, 'y' => $cf_top['y'] + 1.5 ];
        $shoulder_slope = 4.0;  // скос плеча переда
        $f_shoulder_tip = [
            'x' => $fn_base['x'] + $this->params['shoulder_length'],
            'y' => $fn_base['y'] + $shoulder_slope,
        ];

        // Кривая горловины переда (Безье)
        $front_neck_curve = [
            'type'  => 'bezier',
            'start' => $cf_top,
            'cp1'   => [ 'x' => $cf_top['x'] + $fnw * 0.4, 'y' => $cf_top['y'] + $fnd * 0.8 ],
            'cp2'   => [ 'x' => $fn_base['x'] - $fnw * 0.2, 'y' => $fn_base['y'] - 0.5 ],
            'end'   => $fn_base,
        ];

        // Спинка
        $cb_top = [ 'x' => $grid['center_back'], 'y' => $grid['y_shoulder'] ];
        $bn_base = [ 'x' => $cb_top['x'] - $bnw, 'y' => $cb_top['y'] + 0.8 ];
        $back_shoulder_slope = 3.0;
        $b_shoulder_tip = [
            'x' => $bn_base['x'] - $this->params['shoulder_length'],
            'y' => $bn_base['y'] + $back_shoulder_slope,
        ];

        $back_neck_curve = [
            'type'  => 'bezier',
            'start' => $cb_top,
            'cp1'   => [ 'x' => $cb_top['x'] - $bnw * 0.4, 'y' => $cb_top['y'] + $bnd * 0.6 ],
            'cp2'   => [ 'x' => $bn_base['x'] + $bnw * 0.1, 'y' => $bn_base['y'] - 0.3 ],
            'end'   => $bn_base,
        ];

        $this->points['shoulder'] = [
            'front_neck_base'   => $fn_base,
            'front_shoulder_tip'=> $f_shoulder_tip,
            'front_neck_curve'  => $front_neck_curve,
            'back_neck_base'    => $bn_base,
            'back_shoulder_tip' => $b_shoulder_tip,
            'back_neck_curve'   => $back_neck_curve,
        ];
        return $this->points['shoulder'];
    }

    /**
     * Пройма переда и спинки.
     */
    public function calculate_armhole() {
        $shoulder = $this->calculate_shoulder();
        $grid = $this->points['grid'];

        $ftip = $shoulder['front_shoulder_tip'];
        $btip = $shoulder['back_shoulder_tip'];

        // Точки ширины груди и спины на линии груди
        $chest_pt = [
            'x' => $grid['center_front'] + $this->params['chest_width'] / 2,
            'y' => $grid['y_bust'],
        ];
        $back_pt  = [
            'x' => $grid['center_back'] - $this->params['back_width'] / 2,
            'y' => $grid['y_bust'],
        ];
        $underarm = [
            'x' => ( $chest_pt['x'] + $back_pt['x'] ) / 2,
            'y' => $grid['y_bust'],
        ];

        // Пройма переда (кривая Безье)
        $front_armhole = [
            'type'  => 'bezier',
            'start' => $ftip,
            'cp1'   => [
                'x' => $ftip['x'] - 1.5,
                'y' => $ftip['y'] + ( $underarm['y'] - $ftip['y'] ) * 0.45,
            ],
            'cp2'   => [
                'x' => $chest_pt['x'] + 2.0,
                'y' => $underarm['y'] - 1.5,
            ],
            'end'   => $underarm,
        ];

        // Пройма спинки (более выгнутая)
        $back_armhole = [
            'type'  => 'bezier',
            'start' => $btip,
            'cp1'   => [
                'x' => $btip['x'] + 1.0,
                'y' => $btip['y'] + ( $underarm['y'] - $btip['y'] ) * 0.4,
            ],
            'cp2'   => [
                'x' => $back_pt['x'] - 1.5,
                'y' => $underarm['y'] - 2.0,
            ],
            'end'   => $underarm,
        ];

        $this->points['armhole'] = [
            'underarm_point'      => $underarm,
            'front_armhole_curve' => $front_armhole,
            'back_armhole_curve'  => $back_armhole,
        ];
        return $this->points['armhole'];
    }

    /**
     * Построение рукава.
     */
    public function calculate_sleeve() {
        $arm_circ = $this->params['arm_circ'] + self::EASE_SLEEVE_CAP;
        $sleeve_len = $this->params['sleeve_length'];
        $wrist = $this->params['wrist_circ'];

        // Высота оката (примерно 1/3 глубины проймы)
        $cap_height = $this->params['armhole_depth'] * 0.45;
        $sleeve_width = $arm_circ / 2 + 2.5;

        // Базовая сетка рукава
        $top_center = [ 'x' => $sleeve_width / 2, 'y' => 0 ];
        $l_underarm = [ 'x' => 0, 'y' => $cap_height ];
        $r_underarm = [ 'x' => $sleeve_width, 'y' => $cap_height ];
        $l_hem = [ 'x' => 0, 'y' => $cap_height + $sleeve_len ];
        $r_hem = [ 'x' => $sleeve_width, 'y' => $cap_height + $sleeve_len ];

        // Окат рукава (кривая Безье)
        $cap_curve = [
            'type'  => 'bezier',
            'start' => $l_underarm,
            'cp1'   => [ 'x' => $sleeve_width * 0.25, 'y' => $cap_height * 0.7 ],
            'cp2'   => [ 'x' => $sleeve_width * 0.75, 'y' => $cap_height * 0.7 ],
            'end'   => $r_underarm,
        ];

        // Линия низа с учётом заужения к запястью
        $wrist_width = $wrist / 2 + 2;
        $l_hem['x'] = $sleeve_width / 2 - $wrist_width / 2;
        $r_hem['x'] = $sleeve_width / 2 + $wrist_width / 2;

        $this->points['sleeve'] = [
            'top_center'       => $top_center,
            'underarm_left'    => $l_underarm,
            'underarm_right'   => $r_underarm,
            'hem_left'         => $l_hem,
            'hem_right'        => $r_hem,
            'sleeve_cap_curve' => $cap_curve,
        ];
        return $this->points['sleeve'];
    }

    /**
     * Построение трусов (нижняя часть купальника).
     */
    public function calculate_panties() {
        $grid = $this->points['grid'];
        $front_waist = $this->params['front_waist_circ'] / 2 + 0.5;
        $back_waist  = $this->params['back_waist_circ'] / 2 + 0.5;
        $side_len    = $this->params['side_length'];
        $leg_circ    = $this->params['leg_circ'] / 2;
        $step_w      = $this->params['step_width'];

        // Точки талии
        $cf_waist = [ 'x' => $grid['center_front'], 'y' => $grid['y_waist'] ];
        $cb_waist = [ 'x' => $grid['center_back'], 'y' => $grid['y_waist'] ];
        $side_front_waist = [ 'x' => $cf_waist['x'] + $front_waist, 'y' => $grid['y_waist'] ];
        $side_back_waist  = [ 'x' => $cb_waist['x'] - $back_waist, 'y' => $grid['y_waist'] ];

        // Нижняя точка трусов по центру переда (Y = талия + боковая длина)
        $y_low_front = $grid['y_waist'] + $side_len;
        $y_low_back  = $grid['y_waist'] + $side_len + 1.5;  // спинка чуть длиннее

        $crotch_front = [ 'x' => $grid['center_front'], 'y' => $y_low_front ];
        $crotch_back  = [ 'x' => $grid['center_back'], 'y' => $y_low_back ];

        // Линия ноги переда (Безье от боковой талии до центра низа)
        $leg_front_curve = [
            'type'  => 'bezier',
            'start' => $side_front_waist,
            'cp1'   => [
                'x' => $side_front_waist['x'] + 2,
                'y' => $side_front_waist['y'] + $side_len * 0.4,
            ],
            'cp2'   => [
                'x' => $crotch_front['x'] + 2.5,
                'y' => $crotch_front['y'] - 2,
            ],
            'end'   => $crotch_front,
        ];

        $leg_back_curve = [
            'type'  => 'bezier',
            'start' => $side_back_waist,
            'cp1'   => [
                'x' => $side_back_waist['x'] - 2,
                'y' => $side_back_waist['y'] + $side_len * 0.35,
            ],
            'cp2'   => [
                'x' => $crotch_back['x'] - 2.5,
                'y' => $crotch_back['y'] - 2.5,
            ],
            'end'   => $crotch_back,
        ];

        // Боковой шов (соединяем талию с точкой на линии бедра – упрощённо)
        $hip_line_y = $grid['y_hips'];
        $side_hip_front = [ 'x' => $side_front_waist['x'], 'y' => $hip_line_y + 2 ];

        $this->points['panties'] = [
            'front_waist'      => $cf_waist,
            'back_waist'       => $cb_waist,
            'side_waist'       => $side_front_waist, // общая боковая точка талии
            'leg_front_curve'  => $leg_front_curve,
            'leg_back_curve'   => $leg_back_curve,
            'crotch_front'     => $crotch_front,
            'crotch_back'      => $crotch_back,
        ];
        return $this->points['panties'];
    }

    /**
     * Главный метод расчёта юбки.
     *
     * @param string $type
     * @param float  $waist_girth
     * @param float  $hip_girth
     * @param float  $length
     * @param array  $extra
     * @return array
     */
    public function calculate_skirt( $type, $waist_girth, $hip_girth, $length, $extra = [] ) {
        $this->params['skirt_type']   = $type;
        $this->params['skirt_waist']  = $waist_girth;
        $this->params['skirt_hips']   = $hip_girth;
        $this->params['skirt_length'] = $length;

        switch ( $type ) {
            case 'straight':     return $this->calculate_straight_skirt();
            case 'circle':       return $this->calculate_circle_skirt( 'full' );
            case 'half_circle':  return $this->calculate_circle_skirt( 'half' );
            case 'gathered':     return $this->calculate_gathered_skirt();
            case 'tiered':       return $this->calculate_tiered_skirt( $extra['tiers'] ?? 2 );
            default: return [];
        }
    }

    /**
     * Прямая юбка с вытачками.
     */
    private function calculate_straight_skirt() {
        $waist = $this->params['skirt_waist'] + self::EASE_WAIST;
        $hips  = $this->params['skirt_hips']  + self::EASE_HIPS;
        $length = $this->params['skirt_length'];
        $yoke   = $this->params['yoke_height'];

        $front_waist = $waist / 4 + 0.5;
        $back_waist  = $waist / 4 - 0.5;
        $front_hip   = $hips / 4 + 0.5;
        $back_hip    = $hips / 4 - 0.5;
        $hip_depth   = 20; // расстояние от талии до бёдер

        // Вытачки переда
        $fdart_width = $front_waist - $front_hip;
        $fdart_center = $front_waist * 0.4;
        $fdart = [
            'center' => $fdart_center,
            'width'  => max( 0, $fdart_width ),
            'length' => 9,
        ];

        // Вытачки спинки
        $bdart_width = $back_waist - $back_hip;
        $bdart1 = [
            'center' => $back_waist * 0.25,
            'width'  => max( 0, $bdart_width * 0.6 ),
            'length' => 12,
        ];
        $bdart2 = [
            'center' => $back_waist * 0.6,
            'width'  => max( 0, $bdart_width * 0.4 ),
            'length' => 10,
        ];

        $this->points['skirt_straight'] = [
            'front_panel' => [
                'waist_width' => $front_waist,
                'hip_width'   => $front_hip,
                'length'      => $length,
                'dart'        => $fdart,
                'yoke_height' => $yoke,
            ],
            'back_panel' => [
                'waist_width' => $back_waist,
                'hip_width'   => $back_hip,
                'length'      => $length,
                'darts'       => [ $bdart1, $bdart2 ],
                'yoke_height' => $yoke,
            ],
        ];
        return $this->points['skirt_straight'];
    }

    /**
     * Юбка-солнце / полусолнце.
     */
    private function calculate_circle_skirt( $fullness ) {
        $waist = $this->params['skirt_waist'];
        $length = $this->params['skirt_length'];
        $k = ( $fullness === 'full' ) ? 1.0 : 2.0;
        $Rw = ( $waist / ( 2 * M_PI ) ) * $k;
        $Rh = $Rw + $length;

        // Сегментация для печати (если радиус больше 60 см в сложенном виде)
        $max_radius_print = 60; // см
        $segments = 1;
        if ( $Rh > $max_radius_print ) {
            $segments = ceil( $Rh / $max_radius_print );
        }

        $this->points['skirt_circle'] = [
            'radius_waist' => $Rw,
            'radius_hem'   => $Rh,
            'angle'        => ( $fullness === 'full' ) ? 360 : 180,
            'segments'     => $segments,
        ];
        return $this->points['skirt_circle'];
    }

    /**
     * Юбка со сборкой (татьянка).
     */
    private function calculate_gathered_skirt() {
        $coeff = $this->params['flare_coefficient'];
        $waist = $this->params['skirt_waist'];
        $length = $this->params['skirt_length'];
        $panel_width = $waist * $coeff;

        $this->points['skirt_gathered'] = [
            'panel_width' => $panel_width,
            'length'      => $length,
        ];
        return $this->points['skirt_gathered'];
    }

    /**
     * Ярусная юбка.
     */
    private function calculate_tiered_skirt( $tiers = 2 ) {
        $waist = $this->params['skirt_waist'];
        $length = $this->params['skirt_length'];
        $coeff = 1.5; // коэффициент расширения каждого яруса

        $tier_height = $length / $tiers;
        $current_width = $waist;
        $tiers_data = [];
        for ( $i = 0; $i < $tiers; $i++ ) {
            $tiers_data[] = [
                'width'  => $current_width,
                'height' => $tier_height,
            ];
            $current_width *= $coeff;
        }

        $this->points['skirt_tiered'] = [
            'tiers'       => $tiers_data,
            'total_length'=> $length,
        ];
        return $this->points['skirt_tiered'];
    }

    /**
     * Объединение верха и юбки по линии талии (пока просто сливает массивы).
     */
    public function merge_with_bodice( $bodice_points, $skirt_points ) {
        return array_merge( $bodice_points, $skirt_points );
    }

    /**
     * Возвращает все точки выкройки.
     *
     * @return array
     */
    public function get_all_points() {
        $this->calculate_shoulder();
        $this->calculate_armhole();

        if ( $this->params['has_sleeve'] ) {
            $this->calculate_sleeve();
        } else {
            $this->points['sleeve'] = [];
        }

        $this->calculate_panties();

        if ( $this->params['has_skirt'] ) {
            $this->calculate_skirt(
                $this->params['skirt_type'],
                $this->params['skirt_waist'],
                $this->params['skirt_hips'],
                $this->params['skirt_length'],
                [ 'tiers' => $this->params['tiers'] ]
            );
        }

        $this->points['bbox'] = $this->get_bounding_box();
	$this->points['has_skirt']  = $this->params['has_skirt'];
	$this->points['has_sleeve'] = $this->params['has_sleeve'];
        return $this->points;
    }

    /**
     * Вычисляет ограничивающий прямоугольник всех деталей (см).
     *
     * @return array ['minX','maxX','minY','maxY']
     */
    public function get_bounding_box() {
        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = -PHP_FLOAT_MAX;

        $update = function ( $x, $y ) use ( &$minX, &$maxX, &$minY, &$maxY ) {
            if ( is_numeric( $x ) && is_numeric( $y ) ) {
                $minX = min( $minX, $x );
                $maxX = max( $maxX, $x );
                $minY = min( $minY, $y );
                $maxY = max( $maxY, $y );
            }
        };

        $traverse = function ( $arr ) use ( &$traverse, &$update ) {
            if ( isset( $arr['x'], $arr['y'] ) ) {
                $update( $arr['x'], $arr['y'] );
            }
            foreach ( $arr as $value ) {
                if ( is_array( $value ) ) {
                    $traverse( $value );
                }
            }
        };

        $traverse( $this->points );

        // Добавляем поля по 2 см с каждой стороны
        $minX -= 2;
        $maxX += 2;
        $minY -= 2;
        $maxY += 2;

        return compact( 'minX', 'maxX', 'minY', 'maxY' );
    }
}
