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
        };

        this.get(`/search`, params, callback);
    }

    /**
     * Get a specific character
     */
    getCharacter(lodestoneId, callback) {
        this.get(`/character/${lodestoneId}`, {}, callback);
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
}

export default new XIVAPI;
