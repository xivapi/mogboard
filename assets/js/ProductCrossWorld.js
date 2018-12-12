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
        const server = Server.getServer();

        Http.getItemPricesCrossWorld(server, itemId, response => {
            this.ui.html(response);
            callback();
        });
    }
}

export default new ProductCrossWorld;
