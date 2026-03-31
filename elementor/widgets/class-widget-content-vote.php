<?php
/**
 * Elementor Content Vote Widget.
 *
 * @package ContentVote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content_Vote_Widget
 */
class Content_Vote_Widget extends \Elementor\Widget_Base {

	public function get_name(): string {
		return 'content_vote';
	}

	public function get_title(): string {
		return esc_html__( 'Content Vote', 'content-vote' );
	}

	public function get_icon(): string {
		return 'eicon-rating';
	}

	public function get_categories(): array {
		return array( 'content-vote-widgets' );
	}

	public function get_keywords(): array {
		return array( 'vote', 'upvote', 'downvote', 'like', 'dislike', 'feedback', 'rating' );
	}

	public function get_style_depends(): array {
		return array( 'content-vote-public' );
	}

	public function get_script_depends(): array {
		return array( 'content-vote-public' );
	}

	protected function register_controls(): void {

		// =========================================================
		// CONTENT TAB — Section: Content
		// =========================================================
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'section_id_override',
			array(
				'label'       => esc_html__( 'Section ID', 'content-vote' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Auto-detect parent section', 'content-vote' ),
				'description' => esc_html__( 'Leave blank to auto-detect the parent Elementor section ID. Set explicitly to group widgets.', 'content-vote' ),
				'dynamic'     => array( 'active' => false ),
			)
		);

		$this->add_control(
			'show_heading',
			array(
				'label'        => esc_html__( 'Show Heading', 'content-vote' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'content-vote' ),
				'label_off'    => esc_html__( 'Hide', 'content-vote' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'     => esc_html__( 'Heading', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( 'Was this content helpful?', 'content-vote' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'show_counts',
			array(
				'label'        => esc_html__( 'Show Vote Counts', 'content-vote' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'content-vote' ),
				'label_off'    => esc_html__( 'Hide', 'content-vote' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->end_controls_section();

		// =========================================================
		// CONTENT TAB — Section: Icons & Labels
		// =========================================================
		$this->start_controls_section(
			'section_icons',
			array(
				'label' => esc_html__( 'Icons & Labels', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'vote_style',
			array(
				'label'   => esc_html__( 'Button Style', 'content-vote' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'fa'    => esc_html__( 'Font Awesome Icons', 'content-vote' ),
					'emoji' => esc_html__( 'Emoji', 'content-vote' ),
				),
				'default' => 'fa',
			)
		);

		$this->add_control(
			'icon_up',
			array(
				'label'     => esc_html__( 'Positive Icon', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => array( 'value' => 'fas fa-thumbs-up', 'library' => 'fa-solid' ),
				'condition' => array( 'vote_style' => 'fa' ),
			)
		);

		$this->add_control(
			'icon_down',
			array(
				'label'     => esc_html__( 'Negative Icon', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => array( 'value' => 'fas fa-thumbs-down', 'library' => 'fa-solid' ),
				'condition' => array( 'vote_style' => 'fa' ),
			)
		);

		$this->add_control(
			'emoji_up',
			array(
				'label'     => esc_html__( 'Positive Emoji', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => '😊',
				'condition' => array( 'vote_style' => 'emoji' ),
			)
		);

		$this->add_control(
			'emoji_down',
			array(
				'label'     => esc_html__( 'Negative Emoji', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => '😞',
				'condition' => array( 'vote_style' => 'emoji' ),
			)
		);

		$this->add_control(
			'label_up',
			array(
				'label'     => esc_html__( 'Positive Label', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( 'Yes', 'content-vote' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'label_down',
			array(
				'label'   => esc_html__( 'Negative Label', 'content-vote' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'No', 'content-vote' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();

		// =========================================================
		// CONTENT TAB — Section: Layout
		// =========================================================
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// ----- Heading + Buttons container (.cv-widget) -----
		$this->add_control(
			'heading_widget',
			array(
				'label'     => esc_html__( 'Heading + Buttons', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'none',
			)
		);

		$this->add_responsive_control(
			'widget_direction',
			array(
				'label'          => esc_html__( 'Direction', 'content-vote' ),
				'type'           => \Elementor\Controls_Manager::CHOOSE,
				'options'        => array(
					'column' => array(
						'title' => esc_html__( 'Stacked (heading above)', 'content-vote' ),
						'icon'  => 'eicon-navigation-vertical',
					),
					'row'    => array(
						'title' => esc_html__( 'Inline (heading left)', 'content-vote' ),
						'icon'  => 'eicon-navigation-horizontal',
					),
				),
				'default'        => 'column',
				'tablet_default' => 'column',
				'mobile_default' => 'column',
				'toggle'         => false,
				'selectors'      => array(
					'{{WRAPPER}} .cv-widget' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		// justify-content = main axis (vertical when column, horizontal when row).
		$this->add_responsive_control(
			'widget_justify',
			array(
				'label'     => esc_html__( 'Main Axis Align', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start'    => array( 'title' => esc_html__( 'Start', 'content-vote' ),   'icon' => 'eicon-flex eicon-align-start-v' ),
					'center'        => array( 'title' => esc_html__( 'Center', 'content-vote' ),  'icon' => 'eicon-flex eicon-align-center-v' ),
					'flex-end'      => array( 'title' => esc_html__( 'End', 'content-vote' ),     'icon' => 'eicon-flex eicon-align-end-v' ),
					'space-between' => array( 'title' => esc_html__( 'Space Between', 'content-vote' ), 'icon' => 'eicon-flex eicon-justify-space-between-v' ),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget' => 'justify-content: {{VALUE}};',
				),
			)
		);

		// align-items = cross axis (horizontal when column, vertical when row).
		$this->add_responsive_control(
			'widget_align_items',
			array(
				'label'     => esc_html__( 'Cross Axis Align', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Start', 'content-vote' ),  'icon' => 'eicon-flex eicon-align-start-h' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'content-vote' ), 'icon' => 'eicon-flex eicon-align-center-h' ),
					'flex-end'   => array( 'title' => esc_html__( 'End', 'content-vote' ),    'icon' => 'eicon-flex eicon-align-end-h' ),
					'stretch'    => array( 'title' => esc_html__( 'Stretch', 'content-vote' ),'icon' => 'eicon-flex eicon-align-stretch-h' ),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'widget_gap',
			array(
				'label'      => esc_html__( 'Gap (Heading ↔ Buttons)', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array( 'size' => 12, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		// ----- Buttons row (.cv-widget__buttons) -----
		$this->add_control(
			'heading_buttons_row',
			array(
				'label'     => esc_html__( 'Buttons Row', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'buttons_direction',
			array(
				'label'          => esc_html__( 'Direction', 'content-vote' ),
				'type'           => \Elementor\Controls_Manager::CHOOSE,
				'options'        => array(
					'row'    => array(
						'title' => esc_html__( 'Horizontal', 'content-vote' ),
						'icon'  => 'eicon-navigation-horizontal',
					),
					'column' => array(
						'title' => esc_html__( 'Vertical', 'content-vote' ),
						'icon'  => 'eicon-navigation-vertical',
					),
				),
				'default'        => 'row',
				'tablet_default' => 'row',
				'mobile_default' => 'row',
				'toggle'         => false,
				'selectors'      => array(
					'{{WRAPPER}} .cv-widget__buttons' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		// justify-content = main axis of buttons row.
		$this->add_responsive_control(
			'buttons_justify',
			array(
				'label'     => esc_html__( 'Main Axis Align', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start'    => array( 'title' => esc_html__( 'Start', 'content-vote' ),        'icon' => 'eicon-flex eicon-align-start-h' ),
					'center'        => array( 'title' => esc_html__( 'Center', 'content-vote' ),       'icon' => 'eicon-flex eicon-align-center-h' ),
					'flex-end'      => array( 'title' => esc_html__( 'End', 'content-vote' ),          'icon' => 'eicon-flex eicon-align-end-h' ),
					'space-between' => array( 'title' => esc_html__( 'Space Between', 'content-vote' ),'icon' => 'eicon-flex eicon-justify-space-between-h' ),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__buttons' => 'justify-content: {{VALUE}};',
				),
			)
		);

		// align-items = cross axis of buttons row.
		$this->add_responsive_control(
			'buttons_align_items',
			array(
				'label'     => esc_html__( 'Cross Axis Align', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Start', 'content-vote' ),   'icon' => 'eicon-flex eicon-align-start-v' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'content-vote' ),  'icon' => 'eicon-flex eicon-align-center-v' ),
					'flex-end'   => array( 'title' => esc_html__( 'End', 'content-vote' ),     'icon' => 'eicon-flex eicon-align-end-v' ),
					'stretch'    => array( 'title' => esc_html__( 'Stretch', 'content-vote' ), 'icon' => 'eicon-flex eicon-align-stretch-v' ),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__buttons' => 'align-items: {{VALUE}};',
				),
			)
		);

		// Gap between individual buttons.
		$this->add_responsive_control(
			'buttons_gap',
			array(
				'label'      => esc_html__( 'Gap between buttons', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'size' => 12, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget__buttons' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — Section: Container
		// =========================================================
		$this->start_controls_section(
			'section_style_container',
			array(
				'label' => esc_html__( 'Container', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'container_bg',
				'label'    => esc_html__( 'Background', 'content-vote' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .cv-widget',
			)
		);

		$this->add_responsive_control(
			'container_padding',
			array(
				'label'      => esc_html__( 'Padding', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .cv-widget',
			)
		);

		$this->add_responsive_control(
			'container_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_shadow',
				'selector' => '{{WRAPPER}} .cv-widget',
			)
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — Section: Buttons
		// =========================================================
		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => esc_html__( 'Buttons', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		// ----- Button inner layout (icon + label) -----
		$this->add_control(
			'heading_btn_inner',
			array(
				'label'     => esc_html__( 'Button Inner Layout', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'none',
			)
		);

		$this->add_responsive_control(
			'btn_content_direction',
			array(
				'label'          => esc_html__( 'Direction', 'content-vote' ),
				'type'           => \Elementor\Controls_Manager::CHOOSE,
				'options'        => array(
					'column' => array(
						'title' => esc_html__( 'Vertical (icon top)', 'content-vote' ),
						'icon'  => 'eicon-navigation-vertical',
					),
					'row'    => array(
						'title' => esc_html__( 'Horizontal (icon left)', 'content-vote' ),
						'icon'  => 'eicon-navigation-horizontal',
					),
				),
				'default'        => 'column',
				'tablet_default' => 'column',
				'mobile_default' => 'column',
				'toggle'         => false,
				'selectors'      => array(
					'{{WRAPPER}} .cv-widget__btn' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		// justify-content = main axis inside the button.
		$this->add_responsive_control(
			'btn_justify_content',
			array(
				'label'     => esc_html__( 'Main Axis Align', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Start', 'content-vote' ),  'icon' => 'eicon-flex eicon-align-start-v' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'content-vote' ), 'icon' => 'eicon-flex eicon-align-center-v' ),
					'flex-end'   => array( 'title' => esc_html__( 'End', 'content-vote' ),    'icon' => 'eicon-flex eicon-align-end-v' ),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn' => 'justify-content: {{VALUE}};',
				),
			)
		);

		// align-items = cross axis inside the button.
		$this->add_responsive_control(
			'btn_align_items',
			array(
				'label'     => esc_html__( 'Cross Axis Align', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Start', 'content-vote' ),   'icon' => 'eicon-flex eicon-align-start-h' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'content-vote' ),  'icon' => 'eicon-flex eicon-align-center-h' ),
					'flex-end'   => array( 'title' => esc_html__( 'End', 'content-vote' ),     'icon' => 'eicon-flex eicon-align-end-h' ),
					'stretch'    => array( 'title' => esc_html__( 'Stretch', 'content-vote' ), 'icon' => 'eicon-flex eicon-align-stretch-h' ),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_size',
			array(
				'label'          => esc_html__( 'Button Min Size', 'content-vote' ),
				'type'           => \Elementor\Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'em', 'rem' ),
				'range'          => array( 'px' => array( 'min' => 20, 'max' => 120 ) ),
				'default'        => array( 'size' => 56, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 52, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 48, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .cv-widget__btn' => 'min-width: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} * 0.45);',
				),
				'separator'      => 'before',
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_icon_gap',
			array(
				'label'      => esc_html__( 'Icon ↔ Label gap', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'size' => 4, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget__btn' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_style', array( 'separator' => 'before' ) );

		// Tab: Normal.
		$this->start_controls_tab( 'tab_btn_normal', array( 'label' => esc_html__( 'Normal', 'content-vote' ) ) );

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				// No default — background is optional.
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_text_color',
			array(
				'label'     => esc_html__( 'Icon / Text Color', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#555555',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Tab: Hover.
		$this->start_controls_tab( 'tab_btn_hover', array( 'label' => esc_html__( 'Hover', 'content-vote' ) ) );

		$this->add_control(
			'btn_bg_hover',
			array(
				'label'     => esc_html__( 'Background', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				// No default.
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_text_hover',
			array(
				'label'     => esc_html__( 'Icon / Text Color', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Tab: Voted (active state).
		$this->start_controls_tab( 'tab_btn_active', array( 'label' => esc_html__( 'Voted', 'content-vote' ) ) );

		$this->add_control(
			'btn_up_active_bg',
			array(
				'label'     => esc_html__( 'Positive BG', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2196F3',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn--up.is-active' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_up_active_text',
			array(
				'label'     => esc_html__( 'Positive Color', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn--up.is-active' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_down_active_bg',
			array(
				'label'     => esc_html__( 'Negative BG', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#F44336',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn--down.is-active' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_down_active_text',
			array(
				'label'     => esc_html__( 'Negative Color', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn--down.is-active' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'      => 'btn_border',
				'selector'  => '{{WRAPPER}} .cv-widget__btn',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'btn_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'default'    => array(
					'top'      => '50',
					'right'    => '50',
					'bottom'   => '50',
					'left'     => '50',
					'unit'     => '%',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_shadow',
				'selector' => '{{WRAPPER}} .cv-widget__btn',
			)
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — Section: Heading
		// =========================================================
		$this->start_controls_section(
			'section_style_heading',
			array(
				'label'     => esc_html__( 'Heading', 'content-vote' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .cv-widget__heading',
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Color', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__heading' => 'color: {{VALUE}};',
				),
			)
		);

		// Spacing from heading to buttons (when stacked) or between inline elements.
		$this->add_responsive_control(
			'heading_spacing',
			array(
				'label'      => esc_html__( 'Spacing', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget__heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// =========================================================
		// STYLE TAB — Section: Labels & Counts
		// =========================================================
		$this->start_controls_section(
			'section_style_labels',
			array(
				'label' => esc_html__( 'Labels & Counts', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => esc_html__( 'Label Typography', 'content-vote' ),
				'selector' => '{{WRAPPER}} .cv-widget__label',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'count_typography',
				'label'    => esc_html__( 'Count Typography', 'content-vote' ),
				'selector' => '{{WRAPPER}} .cv-widget__count',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$style      = $settings['vote_style'] ?? 'fa';
		$section_id = ! empty( $settings['section_id_override'] )
			? sanitize_html_class( $settings['section_id_override'] )
			: '';

		$counts = array( 'up' => 0, 'down' => 0 );
		if ( ! empty( $section_id ) ) {
			$page_url = trailingslashit( strtolower( home_url( add_query_arg( array() ) ) ) );
			$counts   = Content_Vote_Database::get_counts( $section_id, $page_url );
		}

		$show_counts  = 'yes' === ( $settings['show_counts'] ?? 'yes' );
		$show_heading = 'yes' === ( $settings['show_heading'] ?? 'yes' );
		$label_up     = $settings['label_up'] ?? __( 'Yes', 'content-vote' );
		$label_down   = $settings['label_down'] ?? __( 'No', 'content-vote' );
		?>
		<div class="cv-widget cv-widget--<?php echo esc_attr( $style ); ?>"
			data-cv-section-id="<?php echo esc_attr( $section_id ); ?>"
			data-cv-page-url="<?php echo esc_url( home_url( add_query_arg( array() ) ) ); ?>">

			<?php if ( $show_heading && ! empty( $settings['heading_text'] ) ) : ?>
				<p class="cv-widget__heading"><?php echo esc_html( $settings['heading_text'] ); ?></p>
			<?php endif; ?>

			<div class="cv-widget__buttons">

				<button type="button"
					class="cv-widget__btn cv-widget__btn--up"
					data-vote-type="1"
					aria-label="<?php echo esc_attr( $label_up ); ?>"
					aria-pressed="false">
					<?php if ( 'fa' === $style ) : ?>
						<?php \Elementor\Icons_Manager::render_icon( $settings['icon_up'] ?? array( 'value' => 'fas fa-thumbs-up', 'library' => 'fa-solid' ), array( 'aria-hidden' => 'true', 'class' => 'cv-widget__icon' ) ); ?>
					<?php else : ?>
						<span class="cv-widget__emoji" aria-hidden="true"><?php echo esc_html( $settings['emoji_up'] ?? '😊' ); ?></span>
					<?php endif; ?>
					<span class="cv-widget__label"><?php echo esc_html( $label_up ); ?></span>
					<?php if ( $show_counts ) : ?>
						<span class="cv-widget__count" aria-live="polite"><?php echo (int) $counts['up']; ?></span>
					<?php endif; ?>
				</button>

				<button type="button"
					class="cv-widget__btn cv-widget__btn--down"
					data-vote-type="-1"
					aria-label="<?php echo esc_attr( $label_down ); ?>"
					aria-pressed="false">
					<?php if ( 'fa' === $style ) : ?>
						<?php \Elementor\Icons_Manager::render_icon( $settings['icon_down'] ?? array( 'value' => 'fas fa-thumbs-down', 'library' => 'fa-solid' ), array( 'aria-hidden' => 'true', 'class' => 'cv-widget__icon' ) ); ?>
					<?php else : ?>
						<span class="cv-widget__emoji" aria-hidden="true"><?php echo esc_html( $settings['emoji_down'] ?? '😞' ); ?></span>
					<?php endif; ?>
					<span class="cv-widget__label"><?php echo esc_html( $label_down ); ?></span>
					<?php if ( $show_counts ) : ?>
						<span class="cv-widget__count" aria-live="polite"><?php echo (int) $counts['down']; ?></span>
					<?php endif; ?>
				</button>

			</div><!-- .cv-widget__buttons -->

			<div class="cv-widget__feedback" role="alert" aria-live="polite"></div>

		</div><!-- .cv-widget -->
		<?php
	}

	protected function content_template(): void {
		?>
		<#
		var style        = settings.vote_style || 'fa';
		var showHeading  = 'yes' === settings.show_heading;
		var showCounts   = 'yes' === settings.show_counts;
		var widgetClass  = 'cv-widget cv-widget--' + style;
		var iconUpView, iconDownView;

		if ( 'fa' === style && settings.icon_up && settings.icon_up.value ) {
			iconUpView = elementor.helpers.renderIcon( view, settings.icon_up, { 'aria-hidden': true, 'class': 'cv-widget__icon' }, 'i', 'object' );
		}
		if ( 'fa' === style && settings.icon_down && settings.icon_down.value ) {
			iconDownView = elementor.helpers.renderIcon( view, settings.icon_down, { 'aria-hidden': true, 'class': 'cv-widget__icon' }, 'i', 'object' );
		}
		#>
		<div class="{{ widgetClass }}" data-cv-section-id="" data-cv-page-url="">
			<# if ( showHeading && settings.heading_text ) { #>
				<p class="cv-widget__heading">{{{ settings.heading_text }}}</p>
			<# } #>
			<div class="cv-widget__buttons">
				<button type="button" class="cv-widget__btn cv-widget__btn--up" aria-pressed="false">
					<# if ( 'fa' === style ) { #>
						<# if ( iconUpView ) { #>{{{ iconUpView.value }}}<# } else { #><i class="fas fa-thumbs-up cv-widget__icon" aria-hidden="true"></i><# } #>
					<# } else { #>
						<span class="cv-widget__emoji" aria-hidden="true">{{{ settings.emoji_up || '😊' }}}</span>
					<# } #>
					<span class="cv-widget__label">{{{ settings.label_up || '<?php echo esc_js( __( 'Yes', 'content-vote' ) ); ?>' }}}</span>
					<# if ( showCounts ) { #><span class="cv-widget__count">0</span><# } #>
				</button>
				<button type="button" class="cv-widget__btn cv-widget__btn--down" aria-pressed="false">
					<# if ( 'fa' === style ) { #>
						<# if ( iconDownView ) { #>{{{ iconDownView.value }}}<# } else { #><i class="fas fa-thumbs-down cv-widget__icon" aria-hidden="true"></i><# } #>
					<# } else { #>
						<span class="cv-widget__emoji" aria-hidden="true">{{{ settings.emoji_down || '😞' }}}</span>
					<# } #>
					<span class="cv-widget__label">{{{ settings.label_down || '<?php echo esc_js( __( 'No', 'content-vote' ) ); ?>' }}}</span>
					<# if ( showCounts ) { #><span class="cv-widget__count">0</span><# } #>
				</button>
			</div>
		</div>
		<?php
	}
}
