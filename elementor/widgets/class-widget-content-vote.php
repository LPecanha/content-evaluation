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
		// SECTION: Content
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
			)
		);

		$this->end_controls_section();

		// =========================================================
		// SECTION: Icons & Labels
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

		// Up icon — shown only for FA style.
		$this->add_control(
			'icon_up',
			array(
				'label'     => esc_html__( 'Positive Icon', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-thumbs-up',
					'library' => 'fa-solid',
				),
				'condition' => array( 'vote_style' => 'fa' ),
			)
		);

		$this->add_control(
			'icon_down',
			array(
				'label'     => esc_html__( 'Negative Icon', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-thumbs-down',
					'library' => 'fa-solid',
				),
				'condition' => array( 'vote_style' => 'fa' ),
			)
		);

		// Emoji pickers — shown only for emoji style.
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
				'label'   => esc_html__( 'Positive Label', 'content-vote' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Yes', 'content-vote' ),
				'dynamic' => array( 'active' => true ),
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
		// SECTION: Layout (responsive)
		// =========================================================
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Values are valid CSS flex-direction values (row / column).
		$this->add_responsive_control(
			'layout',
			array(
				'label'     => esc_html__( 'Buttons Direction', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'row'    => array(
						'title' => esc_html__( 'Horizontal', 'content-vote' ),
						'icon'  => 'eicon-navigation-horizontal',
					),
					'column' => array(
						'title' => esc_html__( 'Vertical', 'content-vote' ),
						'icon'  => 'eicon-navigation-vertical',
					),
				),
				'default'         => 'row',
				'tablet_default'  => 'row',
				'mobile_default'  => 'column',
				'toggle'          => false,
				'selectors'       => array(
					'{{WRAPPER}} .cv-widget__buttons' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'alignment',
			array(
				'label'     => esc_html__( 'Alignment', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => esc_html__( 'Start', 'content-vote' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => esc_html__( 'Center', 'content-vote' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'End', 'content-vote' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget'          => 'align-items: {{VALUE}};',
					'{{WRAPPER}} .cv-widget__buttons' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// =========================================================
		// SECTION: Style — Buttons
		// =========================================================
		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => esc_html__( 'Buttons', 'content-vote' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'button_size',
			array(
				'label'      => esc_html__( 'Button Size', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 20, 'max' => 120 ),
				),
				'default'       => array( 'size' => 56, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 52, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 48, 'unit' => 'px' ),
				'selectors'     => array(
					'{{WRAPPER}} .cv-widget__btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} * 0.45);',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
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

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab( 'tab_btn_normal', array( 'label' => esc_html__( 'Normal', 'content-vote' ) ) );

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f0f0f0',
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

		$this->start_controls_tab( 'tab_btn_hover', array( 'label' => esc_html__( 'Hover', 'content-vote' ) ) );

		$this->add_control(
			'btn_bg_hover',
			array(
				'label'     => esc_html__( 'Background', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e0e0e0',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_btn_active', array( 'label' => esc_html__( 'Voted', 'content-vote' ) ) );

		$this->add_control(
			'btn_up_active_color',
			array(
				'label'     => esc_html__( 'Positive Active BG', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2196F3',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn--up.is-active' => 'background-color: {{VALUE}}; color: #fff; border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_down_active_color',
			array(
				'label'     => esc_html__( 'Negative Active BG', 'content-vote' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#F44336',
				'selectors' => array(
					'{{WRAPPER}} .cv-widget__btn--down.is-active' => 'background-color: {{VALUE}}; color: #fff; border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_border',
				'selector' => '{{WRAPPER}} .cv-widget__btn',
			)
		);

		$this->add_responsive_control(
			'btn_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
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
		// SECTION: Style — Heading
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

		$this->add_responsive_control(
			'heading_spacing',
			array(
				'label'      => esc_html__( 'Bottom Spacing', 'content-vote' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'size' => 12, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .cv-widget__heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
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
