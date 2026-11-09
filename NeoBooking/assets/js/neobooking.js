(function ($) {
    $(function () {
        if ($.fn.wpColorPicker) {
            $('.neo-color-field').wpColorPicker();
        }

        $('.neobooking-wrap').each(function () {
            var $wrap = $(this);
            var $dateSelect = $wrap.find('.neobooking-date-select');
            var $timeSelect = $wrap.find('.neobooking-time-select');
            var dataScript = $wrap.find('.neobooking-availability').text();
            var availability = {};

            try {
                availability = JSON.parse(dataScript || '{}');
            } catch (error) {
                availability = {};
            }

            var calendarType = $wrap.data('calendar-type') || 'gregorian';
            var localeMap = {
                gregorian: 'en-US',
                persian: 'en-US-u-ca-persian',
                hijri: 'en-US-u-ca-islamic'
            };

            function formatDate(dateKey) {
                var parts = dateKey.split('-');
                if (parts.length !== 3 || typeof Intl === 'undefined' || typeof Intl.DateTimeFormat === 'undefined') {
                    return dateKey;
                }

                var date = new Date(parts[0], parseInt(parts[1], 10) - 1, parts[2]);
                return new Intl.DateTimeFormat(localeMap[calendarType] || 'en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }).format(date);
            }

            function fillDates() {
                $dateSelect.empty();

                var dates = Object.keys(availability);
                if (!dates.length) {
                    $dateSelect.append($('<option />', { value: '', text: 'No dates available' }));
                    $timeSelect.append($('<option />', { value: '', text: 'No time slots available' }));
                    return;
                }

                dates.forEach(function (dateKey) {
                    $dateSelect.append($('<option />', { value: dateKey, text: formatDate(dateKey) }));
                });

                fillTimes(dates[0]);
            }

            function fillTimes(dateKey) {
                $timeSelect.empty();

                var slots = availability[dateKey] || [];
                if (!slots.length) {
                    $timeSelect.append($('<option />', { value: '', text: 'No time slots available' }));
                    return;
                }

                slots.forEach(function (slot) {
                    $timeSelect.append($('<option />', { value: slot.value, text: slot.label }));
                });
            }

            $dateSelect.on('change', function () {
                fillTimes($(this).val());
            });

            fillDates();
        });
    });
})(jQuery);
