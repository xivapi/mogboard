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
}

export default new Http;
