class Http
{
    /**
     * Get the results from an item category list for a specific id
     *
     * @param id int
     * @param callback function
     */
    getItemCategoryList(id, callback)
    {
        const url = app.url_item_category_list.replace('-id-', id);

        fetch(url, { mode: 'cors' })
            .then(response => response.text())
            .then(callback);
    }

    /**
     * Get prices for an item
     *
     * @param server
     * @param itemId
     * @param callback
     */
    getItemPrices(server, itemId, callback)
    {
        const url = app.url_product_price.replace('-server-', server).replace('-id-', itemId);

        fetch(url, { mode: 'cors' })
            .then(response => response.text())
            .then(callback);
    }

    /**
     * Get price history of an item
     *
     * @param server
     * @param itemId
     * @param callback
     */
    getItemHistory(server, itemId, callback)
    {
        const url = app.url_product_history.replace('-server-', server).replace('-id-', itemId);

        fetch(url, { mode: 'cors' })
            .then(response => response.text())
            .then(callback);
    }
}

export default new Http;
