import Server from './Server';
import Http from './Http';

class ProductPricing
{
    constructor()
    {
        this.ui = null;
    }

    setUi(className)
    {
        this.ui = $(className);
        return this;
    }

    fetch(itemId, callback)
    {
        this.ui.html('<div class="product-loading-text">Loading current prices</div>');

        const server = Server.getServer();

        Http.getItemPrices(server, itemId, response => {
            this.ui.html(response);
            callback();
        });
    }
}

export default new ProductPricing;
