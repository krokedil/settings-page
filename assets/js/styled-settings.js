jQuery(function ($) {
    const krokedil_styled_settings = {
        /**
         * Toggles the Krokedil settings sections.
         */
        toggleSettingsSection: function () {
            $('.krokedil_settings__section_header').on('click', function () {
                krokedil_styled_settings.toggleSectionContent($(this));
            });
        },

        /**
         * Moves the submit button to a new placement.
         */
        moveSubmitButton: function () {
            let $submitBtn = $('.krokedil_settings__gateway_page p.submit');
            let $newPlacement = $('.krokedil_settings__settings_navigation');

            if ($submitBtn.length && $newPlacement.length) {
                $newPlacement.append($submitBtn);
            }
        },

        /**
         * Smooth scrolls to anchor links.
         */
        smoothScroll: function () {
            $(document).on('click', 'a.krokedil_settings__settings_navigation_link', function (event) {
                event.preventDefault();
                let $section = $('#krokedil_section_' + $(this).attr('href').replace('#', ''));

                if(!$section.length) {
                    return;
                }

                history.pushState(null, null, $(this).attr('href'));

                if (!$section.find('.krokedil_settings__section_content').hasClass('active')) {
                    krokedil_styled_settings.toggleSectionContent($section);
                }

                $('html, body').animate({
                    scrollTop: $section.offset().top - 100
                }, 500);
            });
        },

        /**
         * Toggles the content of the settings section.
         */
        toggleSectionContent: function ($section) {
            $section.find('.krokedil_settings__section_toggle')
                    .toggleClass('dashicons-arrow-up-alt2')
                    .toggleClass('dashicons-arrow-down-alt2');

            let $sectionContent = $section.closest('.krokedil_settings__section').find('.krokedil_settings__section_content');
            $sectionContent.toggleClass('active');
        },

        /**
         * Opens the settings section based on the URL hash.
         */
        openSettingsSection: function () {
            // Check if the URL contains a hash
            let sectionId = window.location.hash ?? '';
            let $section = $('#krokedil_section_' + sectionId.replace('#', ''));

            if ($section.length) {
                krokedil_styled_settings.toggleSectionContent($section);
            }
        },

        /**
         * Initializes the events for this file.
         */
        init: function () {
            // Check if the specific class exists in the DOM
            if ( ! $('.krokedil_settings__gateway_page.styled').length ) {
                return;
            }

            $(document)
                .ready(this.toggleSettingsSection)
                .ready(this.moveSubmitButton)
                .ready(this.smoothScroll)
                .ready(this.openSettingsSection);
        },
    };
    krokedil_styled_settings.init();
});