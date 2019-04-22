import XIVAPI from './XIVAPI';

/**
 * todo - this needs some cleaning up
 */
class Search
{
    constructor()
    {
        this.uiInput = $('input.search');
        this.uiView  = $('.search-results-container');
        this.uiLazy = null;

        this.timeout = null;
        this.timeoutDelay = 500;
        this.searching = false;
        this.searchTerm = null;
    }

    watch()
    {
        this.uiInput.on('keyup', event => {
            clearTimeout(this.timeout);
            const searchTerm = $(event.currentTarget).val().trim();

            this.uiInput.removeClass('complete');
            searchTerm.length === 0 ? this.uiInput.removeClass('typing') : this.uiInput.addClass('typing');

            if (searchTerm.length === 0) {
                this.searchTerm = '';
                this.uiView.removeClass('open');
                return;
            }

            if (this.searching || this.searchTerm == searchTerm || searchTerm.length < 2) {
                return;
            }

            // perform search
            this.timeout = setTimeout(() => {
                this.searchTerm = $(event.currentTarget).val().trim();
                this.uiView.addClass('open');
                this.searching = true;

                XIVAPI.search(searchTerm, response => {
                    this.render(response);
                });
            }, this.timeoutDelay);
        });

        this.uiInput.on('click', event => {
            if (this.searchTerm && this.searchTerm.length > 1) {
                this.uiView.addClass('open');
            }
        });

        $(document).mouseup(event => {
            const view = this.uiView;
            const input = this.uiInput;

            // if the target of the click isn't the container nor a descendant of the container
            if (!view.is(event.target) && view.has(event.target).length === 0
                && !input.is(event.target) && input.has(event.target).length === 0) {

                this.uiView.removeClass('open');
                this.uiInput.removeClass('complete typing');
            }
        });

        $(window).on('resize', event => {
            this.setSearchHeight();
        })
    }

    render(response)
    {
        window.scrollTo(0,0);
        this.uiInput.removeClass('typing');
        this.uiInput.addClass('complete');

        this.searching = false;
        const results = [];

        // prep results
        response.Results.forEach((item, i) => {
            const url = mog.url_item.replace('-id-', item.ID);

            results.push(
                `<a href="${url}" class="rarity-${item.Rarity}">
                    <span class="item-icon"><img src="http://xivapi.com/mb/loading.svg" class="lazy" data-src="https://xivapi.com${item.Icon}"></span>
                    <span class="item-level">${item.LevelItem}</span>
                    ${item.Name}
                    <span class="item-category">${item.ItemSearchCategory.Name}</span>
                </a>`
            );
        });

        // <button class="btn-filters"><icon class="xiv-MarketFilter"></icon> Filters</button>


        // render results
        this.uiView.find('.search-results').html(`
            <div class="item-search-header">
                <div>
                    Found ${response.Pagination.Results} / ${response.Pagination.ResultsTotal} for <strong>${this.searchTerm}</strong>
                </div>
                <div>
                </div>
            </div>
            <div data-simplebar class="item-search-list" id="item-search-list">${results.join('')}</div>
        `);

        this.uiLazy = $('.lazy').Lazy({
            // your configuration goes here
            scrollDirection: 'vertical',
            appendScroll: $('.item-search-list'),
            effect: 'fadeIn',
            visibleOnly: false,
            bind: 'event',
        });

        this.setSearchHeight();

        const el = new SimpleBar(document.getElementById('item-search-list'));
        el.getScrollElement().addEventListener('scroll', event => {
            this.uiLazy.data("plugin_lazy").update();
        });

        this.uiView.addClass('open');
    }

    setSearchHeight()
    {
        if (this.searchTerm) {
            // Handle height of search
            const $searchResults = $('.item-search-list');
            const windowHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0) - 260;
            $searchResults.css({ height: `${windowHeight}px`} );
        }
    }
}

export default new Search;
