jQuery(document).ready(function ($) {

    let spinner = `
    <div class="spinner">
        <div class="bounce1"></div>
        <div class="bounce2"></div>
        <div class="bounce3"></div>
    </div>
    `;

    $('.sl-accordion-wrapper').on('click', 'li.sub > a', function (e) {
        e.preventDefault();
        if ( $(this).hasClass('active') ) {
            $(this).removeClass('active');
            $(this).next().slideUp(200);
        } else {
            if (!$(e.target).is('span.category-link')) {

                let self = $(this);
                let cat_id = $(this).data('sl-category-id');

                /* get current category level */

                let categoryLevel = $(this).parents('ul').length;

                /* if subcategories already present just slide down */

                if ( $(this).parent().children('ul.sub-categories').length ) {

                    self.parent().siblings().children('ul').slideUp(200);
                    self.parent().siblings().children('a').removeClass('active');

                    self.addClass('active');
                    self.next().is(':visible') || self.next().slideDown(200);

                } else {
                    
                    /* apply spinner */

                    let _q = $(this).children('.sl-quantities');
                    let _prevState = _q.html();
                    _q.html(spinner);

                    /* gather data */

                    $.ajax({
                        method: 'POST',
                        url: ajax_url,
                        data: {
                            category_id: cat_id,
                            action: 'sl_get_category_data'
                        },
                        success: function (data) {
                            let html = `<ul class="sub-categories sl-acc-lvl${categoryLevel + 1}">`;
                            JSON.parse(data).forEach(function (e) {

                                // sub categories

                                html += `
                            <li class="sub">
                                <a href="#" data-sl-category-id="${e.id}">
                                    <i class="${e.icon}"></i>
                                    <span class="category-link" data-slug="${e.url}">
                                    ${e.name}
                                    </span>
                                    <div class="sl-quantities">${e.quantity}</div>
                                </a>
                            </li>
                        `;
                            });
                            html += '</ul>';

                            self.parent().append(html);
                            _q.html(_prevState);

                            self.parent().siblings().children('ul').slideUp(200);
                            self.parent().siblings().children('a').removeClass('active');

                            self.addClass('active');
                            self.next().is(':visible') || self.next().slideDown(200);
                        },
                        error: function (err) {
                            console.log(err);
                        }
                    });
                }
            } else {
                window.location.href = $(e.target).data('slug');
            }
        }
    });

});