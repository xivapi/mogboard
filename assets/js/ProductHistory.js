import Server from './Server';
import Http from './Http';

class ProductHistory
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

        Http.getItemHistory(server, itemId, response => {
            this.ui.html(response);
            callback();
        });
    }
}

export default new ProductHistory;
