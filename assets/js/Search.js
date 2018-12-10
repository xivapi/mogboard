import XIVAPI from './XIVAPI';

class Search
{
    constructor()
    {
        this.input = $('.search-bar input');
        this.ui    = $('.search-ui');

        this.timeout = null;
        this.timeoutDelay = 200;
        this.searchTerm = null;
    }

    watch()
    {
        this.input.on('keyup', event => {
            clearTimeout(this.timeout);
            const searchTerm = $(event.currentTarget).val().trim();

            if (this.searchTerm === searchTerm || searchTerm.length < 2) {
                this.searchTerm = searchTerm;
                return;
            }

            this.searchTerm = searchTerm;

            // perform search
            this.timeout = setTimeout(() => {
                XIVAPI.search(searchTerm, response => {
                    this.render(response);
                });
            }, this.timeoutDelay);
        });
    }

    render(response)
    {
        const results = [];

        // prep results
        response.Results.forEach((item, i) => {
            const url = mog.url_product.replace('-id-', item.ID);

            results.push(
                `<a href="${url}" class="rarity-${item.Rarity}">
                    <span><img src="http://xivapi.com/mb/loading.svg" class="lazy" data-src="https://xivapi.com${item.Icon}"></span>
                    <span>${item.LevelItem}</span>
                    ${item.Name}
                    <span>${item.ItemSearchCategory.Name}</span>
                </a>`
            );
        });

        // render results
        this.ui.html(`
            <div class="item-search-list">
                <h2>Found ${response.Pagination.Results} / ${response.Pagination.ResultsTotal} for <strong>${this.searchTerm}</strong></h2>
                ${results.join('')}
            </div>
        `);

        new LazyLoad({ elements_selector: ".lazy" });
    }
}

export default new Search;
