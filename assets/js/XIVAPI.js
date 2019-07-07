import Settings from './Settings';

class XIVAPI
{
    get(endpoint, queries, callback)
    {
        queries = queries ? queries : {};
        queries.language = Settings.getLanguage();

        let query = Object.keys(queries)
            .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(queries[k]))
            .join('&');

        endpoint = endpoint +'?'+ query;

        fetch (`https://xivapi.com${endpoint}`, { mode: 'cors' })
            .then(response => response.json())
            .then(callback)
    }

    /**
     * Search for an item
     */
    search(string, callback) {
        let params = {
            indexes: 'item',
            filters: 'ItemSearchCategory.ID>=1',
            columns: 'ID,Icon,Name,LevelItem,Rarity,ItemSearchCategory.Name,ItemSearchCategory.ID,ItemKind.Name',
            string:  string.trim(),
            limit:   100,
            sort_field: 'LevelItem',
            sort_order: 'desc'
        };

        this.get(`/search`, params, callback);
    }

    /**
     * A limited search
     */
    searchLimited(string, callback) {
        let params = {
            indexes: 'item',
            filters: 'ItemSearchCategory.ID>=1',
            columns: 'ID,Name',
            string:  string.trim(),
            limit:   10,
        };

        this.get(`/search`, params, callback);
    }

    /**
     * Search for a character
     */
    searchCharacter(name, server, callback) {
        this.get(`/character/search`, {
            name: name,
            server: server
        }, callback);
    }

    /**
     * Get a specific character
     */
    getCharacter(lodestoneId, callback) {
        this.get(`/character/${lodestoneId}`, {}, callback);
    }

    /**
     * Confirm character verification state
     */
    characterVerification(lodestoneId, token, callback) {
        this.get(`/character/${lodestoneId}/verification`, {
            token: token
        }, callback);
    }

    /**
     * Return information about an item
     */
    getItem(itemId, callback) {
        this.get(`/Item/${itemId}`, {}, callback);
    }

    /**
     * Get a list of servers grouped by their data center
     */
    getServerList(callback) {
        this.get('/servers/dc', {}, callback);
    }

    /**
     *
     */
    getMarketPrices(itemId, server, callback)
    {
        const options = {
            columns: 'Prices,Item',
            servers: server,
        };

        this.get(`/market/item/${itemId}`, options, callback);
    }
}

export default new XIVAPI;
