using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using SaintCoinach;
using SaintCoinach.Ex;
using SaintCoinach.IO;
using SaintCoinach.Libra;
using SaintCoinach.Xiv;
using Directory = System.IO.Directory;
using Item = SaintCoinach.Xiv.Item;

namespace MogboardDataExporter
{
    class Program
    {
        static void Main(string[] args)
        {
            var outputPath = Path.Combine("..", "..", "..", "..", "DataExports");
            
            if (Directory.Exists(outputPath))
                Directory.Delete(outputPath, true);

            Directory.CreateDirectory(outputPath);

            var realm = new ARealmReversed(args[0], Language.English);
            var realmDe = new ARealmReversed(args[0], Language.German);
            var realmFr = new ARealmReversed(args[0], Language.French);
            var realmJp = new ARealmReversed(args[0], Language.Japanese);

            Console.WriteLine("Starting game data export...");

            goto world_export;

            #region Item Export
            var items = realm.GameData.GetSheet<Item>();
            var itemsDe = realmDe.GameData.GetSheet<Item>();
            var itemsFr = realmFr.GameData.GetSheet<Item>();
            var itemsJp = realmJp.GameData.GetSheet<Item>();

            foreach (var category in realm.GameData.GetSheet<ItemSearchCategory>())
            {
                // We don't need those, not for sale
                if (category.Key == 0)
                    continue;

                var output = new List<JObject>();

                foreach (var item in items.Where(item => item.ItemSearchCategory.Key == category.Key))
                {
                    dynamic outputItem = new JObject();

                    outputItem.ID = item.Key;

                    var iconId = (UInt16) item.GetRaw("Icon");
                    outputItem.Icon = $"/i/{GetIconFolder(iconId)}/{iconId}.png";

                    outputItem.Name_en = item.Name.ToString();
                    outputItem.Name_de = itemsDe.First(localItem => localItem.Key == item.Key).Name.ToString();
                    outputItem.Name_fr = itemsFr.First(localItem => localItem.Key == item.Key).Name.ToString();
                    outputItem.Name_jp = itemsJp.First(localItem => localItem.Key == item.Key).Name.ToString();

                    outputItem.LevelItem = item.ItemLevel.Key;
                    outputItem.Rarity = item.Rarity;

                    output.Add(outputItem);
                }

                if (output.Count == 0)
                    continue;

                Console.WriteLine($"Cat {category.Key}: {output.Count}");

                System.IO.File.WriteAllText(Path.Combine(outputPath, $"ItemSearchCategory_{category.Key}.json"), JsonConvert.SerializeObject(output));
            }
            #endregion

            #region ItemSearchCategory Export
            System.IO.File.WriteAllText(Path.Combine(outputPath, "ItemSearchCategory_Keys.json"), JsonConvert.SerializeObject(realm.GameData.GetSheet("ItemSearchCategory").Keys.ToList()));
            #endregion
            
            #region Town Export
            town_export:
            var towns = realm.GameData.GetSheet("Town");
            var townsDe = realmDe.GameData.GetSheet("Town");
            var townsFr = realmFr.GameData.GetSheet("Town");
            var townsJp = realmJp.GameData.GetSheet("Town");

            var outputTowns = new List<JObject>();

            foreach (var town in towns)
            {
                dynamic outputTown = new JObject();

                outputTown.ID = town.Key;

                var iconObj = town.GetRaw("Icon");
                outputTown.Icon = (int) iconObj != 0 ? $"/i/{GetIconFolder((int) iconObj)}/{(int) iconObj}.png" : $"/i/{GetIconFolder(060880)}/060880.png";

                outputTown.Name_en = town.AsString("Name").ToString();
                outputTown.Name_de = townsDe.First(localItem => localItem.Key == town.Key).AsString("Name").ToString();
                outputTown.Name_fr = townsFr.First(localItem => localItem.Key == town.Key).AsString("Name").ToString();
                outputTown.Name_jp = townsJp.First(localItem => localItem.Key == town.Key).AsString("Name").ToString();

                outputTowns.Add(outputTown);
            }

            System.IO.File.WriteAllText(Path.Combine(outputPath, "Town.json"), JsonConvert.SerializeObject(outputTowns));
            #endregion

            #region World Export
            world_export:
            var worlds = realm.GameData.GetSheet("World");

            var outputWorlds = new List<JObject>();

            foreach (var world in worlds)
            {
                dynamic outputWorld = new JObject();

                outputWorld.ID = world.Key;

                outputWorld.Name = world.AsString("Name").ToString();
                outputWorld.DataCenter = (byte) world.GetRaw("DataCenter");
                outputWorld.IsPublic = world.AsBoolean("IsPublic");

                outputWorlds.Add(outputWorld);
            }

            System.IO.File.WriteAllText(Path.Combine(outputPath, "World.json"), JsonConvert.SerializeObject(outputWorlds));
            #endregion

            Console.WriteLine("Done!");
            Console.ReadKey();
        }

        private static string GetIconFolder(int iconId) => (Math.Floor(iconId / 1000d) * 1000).ToString("000000");
    }
}
