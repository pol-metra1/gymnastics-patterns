<?php
/**
 * Pattern Calculator Class
 *
 * Performs all pattern drafting calculations based on adapted methodologies
 * (EMKO SEV, Mueller & Sohn) for rhythmic gymnastics leotards with optional skirt.
 *
 * @package GymnasticsPatterns
 * @since   1.0.0
 */

namespace GymPat;

defined('ABSPATH') || exit;

/**
 * Class PatternCalculator
 *
 * Contains all mathematical models for pattern generation.
 * Outputs points and curves suitable for PDF rendering.
 */
class PatternCalculator {

    /**
     * Measurement parameters (sanitized).
     *
     * @var array
     */
    private $params;

    /**
     * Calculated points and curves.
     *
     * @var array
     */
    private $points = [];

    /**
     * Scale factor for printing (set after bounding box calculation).
     *
     * @var float
     */
    private $scale = 1.0;

    /**
     * Constants for ease allowances (in cm).
     * These values are based on industry standards for stretch fabrics used in leotards.
     */
    const EASE_BUST      = 2.0;  // negative ease possible, but we use positive for basic block
    const EASE_WAIST     = 1.0;
    const EASE_HIPS      = 1.5;
    const EASE_ARMHOLE   = 3.0;
    const EASE_SLEEVE_CAP = 1.5;
    const EASE_NECK      = 0.5;

    /**
     * PatternCalculator constructor.
     *
     * @param array $params Raw measurement parameters.
     */
    public function __construct( array $params ) {
        $this->params = $this->sanitize_params( $params );
        $this->calculate_grid();
    }

    /**
     * Sanitize and set default values for all parameters.
     *
     * @param array $params Raw input.
     * @return array Sanitized parameters.
     */
    private function sanitize_params( $params ) {
        $defaults = [
            // Personal
            'gymnast_name'          => '',
            'gymnast_age'           => 0,

            // Main body measurements (in cm)
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
            'side_length'           => 15.0,   // waist to crotch
            'leg_circ'              => 55.0,
            'step_width'            => 8.0,
            'leotard_length'        => 70.0,   // shoulder to crotch
            'neck_circ'             => 35.0,
            'front_neck_width'      => 10.0,
            'front_neck_depth'      => 5.0,
            'back_neck_width'       => 12.0,
            'back_neck_depth'       => 2.0,
            'side_seam_length'      => 18.0,
            'front_waist_circ'      => 35.0,
            'back_waist_circ'       => 30.0,
            'bust_fullness'         => 3,      // 1-5

            // Skirt
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

        // Use separate skirt measurements if provided, otherwise fallback to main
        if ( empty( $sanitized['skirt_waist'] ) ) {
            $sanitized['skirt_waist'] = $sanitized['waist'];
        }
        if ( empty( $sanitized['skirt_hips'] ) ) {
            $sanitized['skirt_hips'] = $sanitized['hips'];
        }

        return $sanitized;
    }

    /**
     * Calculate basic grid (reference lines).
     *
     * The grid is based on back waist length and bust girth.
     * All coordinates are in centimeters, origin (0,0) at top-left of the bodice block.
     */
    private function calculate_grid() {
        $h      = $this->params['height'];
        $bust   = $this->params['bust'] + self::EASE_BUST;
        $waist  = $this->params['waist'] + self::EASE_WAIST;
        $hips   = $this->params['hips'] + self::EASE_HIPS;

        // Vertical reference lines (Y coordinates from top)
        $y_shoulder     = 0.0;
        $y_bust         = $this->params['armhole_depth'] + 2.0;  // approx bust line
        $y_under_bust   = $y_bust + ( $this->params['bust_height'] * 0.6 );
        $y_waist        = $this->params['back_waist_length'];
        $y_hips         = $y_waist + 20.0;  // standard hip depth
        $y_crotch       = $this->params['leotard_length'];
        $y_hem          = $y_crotch + 5.0;  // extra for hem

        // Horizontal reference lines (X coordinates)
        // Half of bust girth plus ease is the total width of the block
        $block_width = $bust / 2.0 + 4.0;  // 4 cm extra for seams

        $this->points['grid'] = [
            'origin'        => ['x' => 0, 'y' => 0],
            'block_width'   => $block_width,
            'center_front'  => 0,
            'center_back'   => $block_width,
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
     * Calculate shoulder points and neckline for front and back.
     *
     * @return array Points defining shoulder and neck.
     */
    public function calculate_shoulder() {
        $grid   = $this->points['grid'];
        $bust   = $this->params['bust'] + self::EASE_BUST;
        $waist  = $this->params['waist'] + self::EASE_WAIST;

        // Neck widths and depths (front/back)
        $front_neck_w = $this->params['front_neck_width'];
        $front_neck_d = $this->params['front_neck_depth'];
        $back_neck_w  = $this->params['back_neck_width'];
        $back_neck_d  = $this->params['back_neck_depth'];

        // Shoulder slope calculations
        $shoulder_slope_front = 4.0; // cm drop from neck base
        $shoulder_slope_back  = 3.5;

        // Front neck point (center front top)
        $cf_top = ['x' => $grid['center_front'], 'y' => $grid['y_shoulder']];

        // Front neck base (shoulder point at neck)
        $front_neck_base = [
            'x' => $cf_top['x'] + $front_neck_w,
            'y' => $cf_top['y'] + 1.0,
        ];

        // Front shoulder tip
        $front_shoulder_tip = [
            'x' => $front_neck_base['x'] + $this->params['shoulder_length'],
            'y' => $front_neck_base['y'] + $shoulder_slope_front,
        ];

        // Back neck (center back top)
        $cb_top = ['x' => $grid['center_back'], 'y' => $grid['y_shoulder']];

        // Back neck base
        $back_neck_base = [
            'x' => $cb_top['x'] - $back_neck_w,
            'y' => $cb_top['y'] + 0.5,
        ];

        // Back shoulder tip
        $back_shoulder_tip = [
            'x' => $back_neck_base['x'] - $this->params['shoulder_length'],
            'y' => $back_neck_base['y'] + $shoulder_slope_back,
        ];

        // Neck curves (using Bezier control points)
        $front_neck_curve = [
            'type' => 'bezier',
            'start' => $cf_top,
            'end'   => $front_neck_base,
            'cp1'   => ['x' => $cf_top['x'] + $front_neck_w * 0.3, 'y' => $cf_top['y'] + $front_neck_d * 0.8],
            'cp2'   => ['x' => $front_neck_base['x'] - $front_neck_w * 0.2, 'y' => $front_neck_base['y']],
        ];

        $back_neck_curve = [
            'type' => 'bezier',
            'start' => $cb_top,
            'end'   => $back_neck_base,
            'cp1'   => ['x' => $cb_top['x'] - $back_neck_w * 0.3, 'y' => $cb_top['y'] + $back_neck_d * 0.6],
            'cp2'   => ['x' => $back_neck_base['x'] + $back_neck_w * 0.1, 'y' => $back_neck_base['y']],
        ];

        $this->points['shoulder'] = [
            'front_neck_base'   => $front_neck_base,
            'front_shoulder_tip'=> $front_shoulder_tip,
            'front_neck_curve'  => $front_neck_curve,
            'back_neck_base'    => $back_neck_base,
            'back_shoulder_tip' => $back_shoulder_tip,
            'back_neck_curve'   => $back_neck_curve,
        ];

        return $this->points['shoulder'];
    }

    /**
     * Calculate armhole curves for front and back.
     *
     * @return array Armhole points and curves.
     */
    public function calculate_armhole() {
        $grid = $this->points['grid'];
        $shoulder = $this->calculate_shoulder();

        $front_tip = $shoulder['front_shoulder_tip'];
        $back_tip  = $shoulder['back_shoulder_tip'];

        // Armhole depth line (bust line)
        $y_armhole = $grid['y_bust'];

        // Chest width and back width points on bust line
        $chest_width_point = [
            'x' => $grid['center_front'] + $this->params['chest_width'] / 2.0,
            'y' => $y_armhole,
        ];
        $back_width_point = [
            'x' => $grid['center_back'] - $this->params['back_width'] / 2.0,
            'y' => $y_armhole,
        ];

        // Underarm point (midpoint between chest and back width points)
        $underarm_x = ( $chest_width_point['x'] + $back_width_point['x'] ) / 2.0;
        $underarm_point = ['x' => $underarm_x, 'y' => $y_armhole];

        // Armhole curves using Bezier
        // Front armhole: from shoulder tip to underarm, concave inward
        $front_armhole_curve = [
            'type' => 'bezier',
            'start' => $front_tip,
            'end'   => $underarm_point,
            'cp1'   => [
                'x' => $front_tip['x'] - 1.0,
                'y' => $front_tip['y'] + ( $y_armhole - $front_tip['y'] ) * 0.4,
            ],
            'cp2'   => [
                'x' => $chest_width_point['x'] + 1.5,
                'y' => $y_armhole - 2.0,
            ],
        ];

        // Back armhole: from shoulder tip to underarm, more curved
        $back_armhole_curve = [
            'type' => 'bezier',
            'start' => $back_tip,
            'end'   => $underarm_point,
            'cp1'   => [
                'x' => $back_tip['x'] + 1.0,
                'y' => $back_tip['y'] + ( $y_armhole - $back_tip['y'] ) * 0.35,
            ],
            'cp2'   => [
                'x' => $back_width_point['x'] - 1.0,
                'y' => $y_armhole - 2.5,
            ],
        ];

        $this->points['armhole'] = [
            'underarm_point'      => $underarm_point,
            'front_armhole_curve' => $front_armhole_curve,
            'back_armhole_curve'  => $back_armhole_curve,
        ];

        return $this->points['armhole'];
    }

    /**
     * Calculate sleeve pattern.
     *
     * @return array Sleeve points.
     */
    public function calculate_sleeve() {
        $arm_circ = $this->params['arm_circ'] + self::EASE_SLEEVE_CAP;
        $sleeve_length = $this->params['sleeve_length'];
        $wrist = $this->params['wrist_circ'];

        // Sleeve cap height (standard ~1/3 of armhole depth)
        $cap_height = $this->params['armhole_depth'] * 0.4;

        // Sleeve width at bicep = arm_circ / 2 + ease
        $sleeve_width = $arm_circ / 2.0 + 2.0;

        // Basic points
        $top_center = ['x' => $sleeve_width / 2.0, 'y' => 0];
        $underarm_left = ['x' => 0, 'y' => $cap_height];
        $underarm_right = ['x' => $sleeve_width, 'y' => $cap_height];
        $hem_left = ['x' => 0, 'y' => $cap_height + $sleeve_length];
        $hem_right = ['x' => $sleeve_width, 'y' => $cap_height + $sleeve_length];

        // Sleeve cap curve (Bezier)
        $sleeve_cap_curve = [
            'type' => 'bezier',
            'start' => $underarm_left,
            'end'   => $underarm_right,
            'cp1'   => ['x' => $sleeve_width * 0.2, 'y' => $cap_height * 0.7],
            'cp2'   => ['x' => $sleeve_width * 0.8, 'y' => $cap_height * 0.7],
        ];

        $this->points['sleeve'] = [
            'top_center'       => $top_center,
            'underarm_left'    => $underarm_left,
            'underarm_right'   => $underarm_right,
            'hem_left'         => $hem_left,
            'hem_right'        => $hem_right,
            'sleeve_cap_curve' => $sleeve_cap_curve,
        ];

        return $this->points['sleeve'];
    }

    /**
     * Calculate panties (bottom part of leotard).
     *
     * @return array Panties points and curves.
     */
    public function calculate_panties() {
        $grid = $this->points['grid'];
        $waist_y = $grid['y_waist'];
        $crotch_y = $grid['y_crotch'];
        $hips_y = $grid['y_hips'];

        $front_waist = $this->params['front_waist_circ'] / 2.0 + 0.5;
        $back_waist = $this->params['back_waist_circ'] / 2.0 + 0.5;
        $hips_total = $this->params['hips'] + self::EASE_HIPS;
        $hips_half = $hips_total / 2.0;
        $step_width = $this->params['step_width'];

        // Center front and back at waist
        $cf_waist = ['x' => $grid['center_front'], 'y' => $waist_y];
        $cb_waist = ['x' => $grid['center_back'], 'y' => $waist_y];

        // Side seam at waist
        $side_waist_x = $cf_waist['x'] + $front_waist;
        $side_waist = ['x' => $side_waist_x, 'y' => $waist_y];

        // Hip points
        $cf_hips = ['x' => $grid['center_front'], 'y' => $hips_y];
        $cb_hips = ['x' => $grid['center_back'], 'y' => $hips_y];
        $side_hips_x = $cf_hips['x'] + $hips_half / 2.0; // approximate

        // Crotch points
        $cf_crotch = ['x' => $grid['center_front'], 'y' => $crotch_y];
        $cb_crotch = ['x' => $grid['center_back'], 'y' => $crotch_y];

        // Leg opening curve (front)
        $leg_front_curve = [
            'type' => 'bezier',
            'start' => $side_waist,
            'end'   => $cf_crotch,
            'cp1'   => ['x' => $side_waist['x'] + 2.0, 'y' => $hips_y],
            'cp2'   => ['x' => $cf_crotch['x'] + 3.0, 'y' => $crotch_y - 2.0],
        ];

        // Back leg opening
        $leg_back_curve = [
            'type' => 'bezier',
            'start' => ['x' => $side_waist['x'] + $back_waist, 'y' => $waist_y],
            'end'   => $cb_crotch,
            'cp1'   => ['x' => $cb_crotch['x'] - 2.0, 'y' => $hips_y],
            'cp2'   => ['x' => $cb_crotch['x'] - 3.0, 'y' => $crotch_y - 2.0],
        ];

        $this->points['panties'] = [
            'front_waist'      => $cf_waist,
            'back_waist'       => $cb_waist,
            'side_waist'       => $side_waist,
            'leg_front_curve'  => $leg_front_curve,
            'leg_back_curve'   => $leg_back_curve,
            'crotch_point'     => $cf_crotch, // simplified
        ];

        return $this->points['panties'];
    }

    /**
     * Main skirt calculation dispatcher.
     *
     * @param string $type        Skirt type.
     * @param float  $waist_girth Waist circumference.
     * @param float  $hip_girth   Hip circumference.
     * @param float  $length      Skirt length.
     * @param array  $extra       Additional parameters.
     * @return array Skirt points.
     */
    public function calculate_skirt( $type, $waist_girth, $hip_girth, $length, $extra = [] ) {
        $this->params['skirt_type']   = $type;
        $this->params['skirt_waist']  = $waist_girth;
        $this->params['skirt_hips']   = $hip_girth;
        $this->params['skirt_length'] = $length;

        switch ( $type ) {
            case 'straight':
                return $this->calculate_straight_skirt();
            case 'circle':
                return $this->calculate_circle_skirt( 'full' );
            case 'half_circle':
                return $this->calculate_circle_skirt( 'half' );
            case 'gathered':
                return $this->calculate_gathered_skirt();
            case 'tiered':
                return $this->calculate_tiered_skirt( $extra['tiers'] ?? 2 );
            default:
                return [];
        }
    }

    /**
     * Straight skirt with darts.
     *
     * @return array
     */
    public function calculate_straight_skirt() {
        $waist = $this->params['skirt_waist'] + self::EASE_WAIST;
        $hips  = $this->params['skirt_hips'] + self::EASE_HIPS;
        $length = $this->params['skirt_length'];
        $yoke = $this->params['yoke_height'];

        // Half measurements for front/back panels
        $front_waist = $waist / 4.0 + 0.5;
        $back_waist  = $waist / 4.0 - 0.5;
        $front_hips  = $hips / 4.0 + 0.5;
        $back_hips   = $hips / 4.0 - 0.5;

        // Dart calculations (standard 2 darts on back, 1 on front)
        $front_dart_width = 2.0;
        $back_dart_width  = 3.0;
        $hip_depth = 20.0; // standard distance from waist to hip

        $this->points['skirt_straight'] = [
            'front_panel' => [
                'waist_width'  => $front_waist,
                'hip_width'    => $front_hips,
                'length'       => $length,
                'dart'         => [
                    'position' => $front_waist * 0.4,
                    'width'    => $front_dart_width,
                    'length'   => 9.0,
                ],
            ],
            'back_panel' => [
                'waist_width'  => $back_waist,
                'hip_width'    => $back_hips,
                'length'       => $length,
                'darts'        => [
                    ['position' => $back_waist * 0.25, 'width' => $back_dart_width * 0.5, 'length' => 12.0],
                    ['position' => $back_waist * 0.5,  'width' => $back_dart_width * 0.5, 'length' => 10.0],
                ],
            ],
            'yoke_height' => $yoke,
        ];

        return $this->points['skirt_straight'];
    }

    /**
     * Circle skirt (full or half).
     *
     * @param string $fullness 'full' or 'half'.
     * @return array
     */
    public function calculate_circle_skirt( $fullness = 'full' ) {
        $waist = $this->params['skirt_waist'];
        $length = $this->params['skirt_length'];
        $k = ( $fullness === 'full' ) ? 1.0 : 2.0;
        $R_waist = ( $waist / ( 2 * M_PI ) ) * $k;
        $R_hem = $R_waist + $length;
        $angle = ( $fullness === 'full' ) ? 360.0 : 180.0;

        // If radius is large, we split into segments for printing
        $segments = 1;
        $max_radius_print = 60.0; // cm in folded state
        if ( $R_hem > $max_radius_print ) {
            $segments = ceil( $R_hem / $max_radius_print );
        }

        $this->points['skirt_circle'] = [
            'radius_waist' => $R_waist,
            'radius_hem'   => $R_hem,
            'angle'        => $angle,
            'segments'     => $segments,
            'fullness'     => $fullness,
        ];

        return $this->points['skirt_circle'];
    }

    /**
     * Gathered skirt.
     *
     * @return array
     */
    public function calculate_gathered_skirt() {
        $coeff = $this->params['flare_coefficient'];
        $waist = $this->params['skirt_waist'];
        $length = $this->params['skirt_length'];

        $panel_width = $waist * $coeff;

        $this->points['skirt_gathered'] = [
            'panel_width' => $panel_width,
            'length'      => $length,
            'gather_ratio'=> $coeff,
        ];

        return $this->points['skirt_gathered'];
    }

    /**
     * Tiered skirt.
     *
     * @param int $tiers Number of tiers.
     * @return array
     */
    public function calculate_tiered_skirt( $tiers = 2 ) {
        $waist = $this->params['skirt_waist'];
        $length = $this->params['skirt_length'];
        $coeff = 1.5; // each tier is 1.5x wider

        $tier_height = $length / $tiers;
        $tiers_data = [];
        $current_width = $waist;

        for ( $i = 0; $i < $tiers; $i++ ) {
            $tiers_data[] = [
                'tier'   => $i + 1,
                'width'  => $current_width,
                'height' => $tier_height,
            ];
            $current_width *= $coeff;
        }

        $this->points['skirt_tiered'] = [
            'tiers'       => $tiers_data,
            'total_length'=> $length,
            'coefficient' => $coeff,
        ];

        return $this->points['skirt_tiered'];
    }

    /**
     * Merge bodice and skirt points at waistline.
     *
     * @param array $bodice_points
     * @param array $skirt_points
     * @return array Combined points.
     */
    public function merge_with_bodice( $bodice_points, $skirt_points ) {
        // In a full implementation, we would adjust skirt waist to match bodice waist.
        // Here we simply combine arrays.
        return array_merge( $bodice_points, $skirt_points );
    }

    /**
     * Get all calculated points and curves.
     *
     * @return array
     */
    public function get_all_points() {
        // Ensure all calculations are performed
        $this->calculate_shoulder();
        $this->calculate_armhole();
        $this->calculate_sleeve();
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

        // Add bounding box for scaling
        $this->points['bbox'] = $this->get_bounding_box();

        return $this->points;
    }

    /**
     * Compute bounding box of all pattern pieces.
     *
     * @return array ['minX', 'maxX', 'minY', 'maxY']
     */
    public function get_bounding_box() {
        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = -PHP_FLOAT_MAX;

        // Helper to update bounds from a point
        $update = function( $x, $y ) use ( &$minX, &$maxX, &$minY, &$maxY ) {
            if ( ! is_numeric( $x ) || ! is_numeric( $y ) ) return;
            $minX = min( $minX, $x );
            $maxX = max( $maxX, $x );
            $minY = min( $minY, $y );
            $maxY = max( $maxY, $y );
        };

        // Recursively traverse points array
        $traverse = function( $arr ) use ( &$traverse, &$update ) {
            if ( isset( $arr['x'] ) && isset( $arr['y'] ) && is_numeric( $arr['x'] ) && is_numeric( $arr['y'] ) ) {
                $update( $arr['x'], $arr['y'] );
            }
            if ( isset( $arr['start'] ) ) {
                $traverse( $arr['start'] );
                $traverse( $arr['end'] );
                if ( isset( $arr['cp1'] ) ) $traverse( $arr['cp1'] );
                if ( isset( $arr['cp2'] ) ) $traverse( $arr['cp2'] );
            }
            foreach ( $arr as $value ) {
                if ( is_array( $value ) ) {
                    $traverse( $value );
                }
            }
        };

        $traverse( $this->points );

        // Add margins
        $minX -= 5.0;
        $maxX += 5.0;
        $minY -= 5.0;
        $maxY += 5.0;

        return [
            'minX' => $minX,
            'maxX' => $maxX,
            'minY' => $minY,
            'maxY' => $maxY,
        ];
    }
}
