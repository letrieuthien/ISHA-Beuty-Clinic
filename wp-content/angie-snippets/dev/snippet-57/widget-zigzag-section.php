<?php
namespace AngieSnippets;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZigZag_Section_246455eb extends \Elementor\Widget_Base {
    public function get_name() { return 'zigzag_section_246455eb'; }
    public function get_title() { return esc_html__( 'Zig Zag Section', 'angie-snippets' ); }
    public function get_icon() { return 'eicon-ehp-zigzag'; }
    public function get_categories() { return [ 'angie-widgets', 'general' ]; }
    public function get_style_depends() { return [ 'zigzag-section-style-246455eb' ]; }

    protected function register_controls() {
        $this->start_controls_section( 'section_content', [
            'label' => esc_html__( 'Content', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control( 'section_title', [
            'label' => esc_html__( 'Section Title', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => esc_html__( 'Features', 'angie-snippets' ),
            'label_block' => true,
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control( 'image', [
            'label' => esc_html__( 'Image', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [ 'url' => \Elementor\Utils::get_placeholder_image_src() ],
        ]);
        $repeater->add_control( 'subtitle', [
            'label' => esc_html__( 'Subtitle', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => esc_html__( 'Sub Title', 'angie-snippets' ),
            'label_block' => true,
        ]);
        $repeater->add_control( 'text', [
            'label' => esc_html__( 'Text', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => esc_html__( 'Description text goes here.', 'angie-snippets' ),
        ]);
        $repeater->add_control( 'cta_text', [
            'label' => esc_html__( 'CTA Text', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => esc_html__( 'Learn More', 'angie-snippets' ),
        ]);
        $repeater->add_control( 'cta_link', [
            'label' => esc_html__( 'CTA Link', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => esc_html__( 'https://your-link.com', 'angie-snippets' ),
            'default' => [ 'url' => '#' ],
        ]);

        $this->add_control( 'items', [
            'label' => esc_html__( 'Zig Zag Items', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [
                [ 'subtitle' => esc_html__( 'First Item', 'angie-snippets' ), 'text' => esc_html__( 'Description for the first item.', 'angie-snippets' ) ],
                [ 'subtitle' => esc_html__( 'Second Item', 'angie-snippets' ), 'text' => esc_html__( 'Description for the second item.', 'angie-snippets' ) ],
            ],
            'title_field' => '{{{ subtitle }}}',
        ]);
        
        $this->add_control( 'direction_rtl', [
            'label' => esc_html__( 'RTL Mode', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => esc_html__( 'Yes', 'angie-snippets' ),
            'label_off' => esc_html__( 'No', 'angie-snippets' ),
            'return_value' => 'yes',
            'default' => '',
            'prefix_class' => 'zz-rtl-',
        ]);

        $this->end_controls_section();

        // Style Section: General
        $this->start_controls_section( 'section_style_general', [
            'label' => esc_html__( 'General Settings', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        
        $this->add_responsive_control( 'item_gap', [
            'label' => esc_html__( 'Gap Between Items', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
            'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
            'selectors' => [ '{{WRAPPER}} .zz-items' => 'gap: {{SIZE}}{{UNIT}};' ],
        ]);
        
        $this->add_responsive_control( 'column_gap', [
            'label' => esc_html__( 'Column Gap (Image/Text)', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
            'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
            'selectors' => [ '{{WRAPPER}} .zz-item' => 'gap: {{SIZE}}{{UNIT}};' ],
        ]);

        $this->end_controls_section();

        // Style Section: Title
        $this->start_controls_section( 'section_style_title', [
            'label' => esc_html__( 'Section Title', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control( 'title_color', [
            'label' => esc_html__( 'Color', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_PRIMARY,
            ],
            'selectors' => [ '{{WRAPPER}} .zz-title' => 'color: {{VALUE}};' ],
        ]);
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name' => 'title_typography',
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_PRIMARY,
            ],
            'selector' => '{{WRAPPER}} .zz-title',
        ]);
        $this->add_responsive_control( 'title_margin', [
            'label' => esc_html__( 'Margin', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
            'selectors' => [ '{{WRAPPER}} .zz-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);
        $this->end_controls_section();

        // Style Section: Subtitle
        $this->start_controls_section( 'section_style_subtitle', [
            'label' => esc_html__( 'Subtitle', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control( 'subtitle_color', [
            'label' => esc_html__( 'Color', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_SECONDARY,
            ],
            'selectors' => [ '{{WRAPPER}} .zz-subtitle' => 'color: {{VALUE}};' ],
        ]);
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name' => 'subtitle_typography',
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_SECONDARY,
            ],
            'selector' => '{{WRAPPER}} .zz-subtitle',
        ]);
        $this->add_responsive_control( 'subtitle_margin', [
            'label' => esc_html__( 'Margin', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
            'selectors' => [ '{{WRAPPER}} .zz-subtitle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);
        $this->end_controls_section();

        // Style Section: Text
        $this->start_controls_section( 'section_style_text', [
            'label' => esc_html__( 'Text', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control( 'text_color', [
            'label' => esc_html__( 'Color', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_TEXT,
            ],
            'selectors' => [ '{{WRAPPER}} .zz-text' => 'color: {{VALUE}};' ],
        ]);
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name' => 'text_typography',
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_TEXT,
            ],
            'selector' => '{{WRAPPER}} .zz-text',
        ]);
        $this->add_responsive_control( 'text_margin', [
            'label' => esc_html__( 'Margin', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
            'selectors' => [ '{{WRAPPER}} .zz-text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);
        $this->end_controls_section();

        // Style Section: Button
        $this->start_controls_section( 'section_style_button', [
            'label' => esc_html__( 'Button', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name' => 'button_typography',
            'global' => [
                'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_ACCENT,
            ],
            'selector' => '{{WRAPPER}} .zz-cta',
        ]);
        
        $this->start_controls_tabs( 'tabs_button_style' );
        $this->start_controls_tab( 'tab_button_normal', [ 'label' => esc_html__( 'Normal', 'angie-snippets' ) ] );
        $this->add_control( 'button_text_color', [
            'label' => esc_html__( 'Text Color', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .zz-cta' => 'color: {{VALUE}};' ],
        ]);
        $this->add_group_control( \Elementor\Group_Control_Background::get_type(), [
            'name' => 'button_background',
            'types' => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .zz-cta',
        ]);
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name' => 'button_border',
            'selector' => '{{WRAPPER}} .zz-cta',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab( 'tab_button_hover', [ 'label' => esc_html__( 'Hover', 'angie-snippets' ) ] );
        $this->add_control( 'button_hover_color', [
            'label' => esc_html__( 'Text Color', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .zz-cta:hover' => 'color: {{VALUE}};' ],
        ]);
        $this->add_group_control( \Elementor\Group_Control_Background::get_type(), [
            'name' => 'button_background_hover',
            'types' => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .zz-cta:hover',
        ]);
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name' => 'button_border_hover',
            'selector' => '{{WRAPPER}} .zz-cta:hover',
        ]);
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control( 'button_padding', [
            'label' => esc_html__( 'Padding', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
            'selectors' => [ '{{WRAPPER}} .zz-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            'separator' => 'before',
        ]);
        $this->add_responsive_control( 'button_border_radius', [
            'label' => esc_html__( 'Border Radius', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
            'selectors' => [ '{{WRAPPER}} .zz-cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);
        $this->end_controls_section();
        
        // Style Section: Image
        $this->start_controls_section( 'section_style_image', [
            'label' => esc_html__( 'Image', 'angie-snippets' ),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_responsive_control( 'image_border_radius', [
            'label' => esc_html__( 'Border Radius', 'angie-snippets' ),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
            'selectors' => [ '{{WRAPPER}} .zz-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);
        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name' => 'image_box_shadow',
            'selector' => '{{WRAPPER}} .zz-image img',
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="zz-section-246455eb">
            <?php if ( ! empty( $settings['section_title'] ) ) : ?>
                <h2 class="zz-title"><?php echo esc_html( $settings['section_title'] ); ?></h2>
            <?php endif; ?>
            <div class="zz-items">
                <?php foreach ( $settings['items'] as $index => $item ) : ?>
                    <div class="zz-item <?php echo $index % 2 !== 0 ? 'zz-reverse' : ''; ?>">
                        <div class="zz-image">
                            <?php if ( ! empty( $item['image']['url'] ) ) : ?>
                                <img src="<?php echo esc_url( $item['image']['url'] ); ?>" alt="<?php echo esc_attr( $item['subtitle'] ); ?>" />
                            <?php endif; ?>
                        </div>
                        <div class="zz-content">
                            <?php if ( ! empty( $item['subtitle'] ) ) : ?>
                                <h3 class="zz-subtitle"><?php echo esc_html( $item['subtitle'] ); ?></h3>
                            <?php endif; ?>
                            <?php if ( ! empty( $item['text'] ) ) : ?>
                                <div class="zz-text"><?php echo wp_kses_post( $item['text'] ); ?></div>
                            <?php endif; ?>
                            <?php if ( ! empty( $item['cta_text'] ) && ! empty( $item['cta_link']['url'] ) ) : 
                                $target = $item['cta_link']['is_external'] ? ' target="_blank"' : '';
                                $nofollow = $item['cta_link']['nofollow'] ? ' rel="nofollow"' : '';
                            ?>
                                <a href="<?php echo esc_url( $item['cta_link']['url'] ); ?>" class="zz-cta"<?php echo $target . $nofollow; ?>>
                                    <?php echo esc_html( $item['cta_text'] ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <div class="zz-section-246455eb">
            <# if ( settings.section_title ) { #>
                <h2 class="zz-title">{{{ settings.section_title }}}</h2>
            <# } #>
            <div class="zz-items">
                <# _.each( settings.items, function( item, index ) { 
                    var reverseClass = ( index % 2 !== 0 ) ? 'zz-reverse' : '';
                #>
                    <div class="zz-item {{{ reverseClass }}}">
                        <div class="zz-image">
                            <# if ( item.image.url ) { #>
                                <img src="{{{ item.image.url }}}" alt="{{{ item.subtitle }}}" />
                            <# } #>
                        </div>
                        <div class="zz-content">
                            <# if ( item.subtitle ) { #>
                                <h3 class="zz-subtitle">{{{ item.subtitle }}}</h3>
                            <# } #>
                            <# if ( item.text ) { #>
                                <div class="zz-text">{{{ item.text }}}</div>
                            <# } #>
                            <# if ( item.cta_text && item.cta_link.url ) { #>
                                <a href="{{{ item.cta_link.url }}}" class="zz-cta">
                                    {{{ item.cta_text }}}
                                </a>
                            <# } #>
                        </div>
                    </div>
                <# } ); #>
            </div>
        </div>
        <?php
    }
}
