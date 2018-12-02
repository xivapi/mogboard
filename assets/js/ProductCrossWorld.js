import Server from './Server';
import Http from './Http';

class ProductCrossWorld
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
        this.ui.html('<div class="product-loading-text2">cross-world prices and purchase history</div>');

        const server = Server.getServer();

        Http.getItemPricesCrossWorld(server, itemId, response => {
            this.ui.html(response);
            callback();
        });
    }
}

export default new ProductCrossWorld;
