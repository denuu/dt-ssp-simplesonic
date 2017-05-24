# dt-ssp-simplesonic
Datatables SSP SimpleSonic
# Currently being converted into plugin-friendly format

# What's the problem?
Serverside Datatables is great, and many people tweak the popularized `simple()` function to work with complex queries. But there's a big downfall. With large datasets (thousands of pages), flip through the first few pages and it works quickly. Flip to page 1500 and you're greeted with a lengthy load time.

# Why does this happen?
In the case of MySQL, very large offsets (such as `LIMIT 1400, 100`), the initial 1400 results will still be scanned through despite only wanting to see reults 1400 through 1500.

# How does it work?
It's quick to grab just the keys with the offset as the query won't look through all the data associated with them. Grab the range of offset keys, and join your search query as a table on the resulting array of keys. This is incredibly efficient.
