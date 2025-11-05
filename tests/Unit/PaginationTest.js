/**
 * Test script to verify pagination behavior with grouped records
 * This simulates the Welcome.vue pagination logic
 */

// Mock data that simulates records from the API
const mockRawRecords = [
    { id: 1, title: 'Book A', accession_number: '001', status: 'available' },
    { id: 2, title: 'Book A', accession_number: '002', status: 'available' }, // Same title as above
    { id: 3, title: 'Book B', accession_number: '003', status: 'available' },
    { id: 4, title: 'Book C', accession_number: '004', status: 'borrowed' },
    { id: 5, title: 'Book C', accession_number: '005', status: 'available' }, // Same title as above
    { id: 6, title: 'Book D', accession_number: '006', status: 'available' },
    { id: 7, title: 'Book E', accession_number: '007', status: 'available' },
    { id: 8, title: 'Book F', accession_number: '008', status: 'available' },
    { id: 9, title: 'Book G', accession_number: '009', status: 'available' },
    { id: 10, title: 'Book H', accession_number: '010', status: 'available' }
];

// Simulate the grouping function
function groupRecordsByTitle(records) {
    const titleGroups = new Map();

    records.forEach(record => {
        const title = record.title;
        if (!titleGroups.has(title)) {
            titleGroups.set(title, []);
        }
        titleGroups.get(title).push(record);
    });

    const groupedRecords = [];
    titleGroups.forEach((copies) => {
        const representative = copies[0];
        const groupedRecord = {
            id: representative.id,
            title: representative.title,
            accession_number: representative.accession_number,
            status: representative.status,
            copy_count: copies.length,
            copies: copies
        };
        groupedRecords.push(groupedRecord);
    });

    return groupedRecords;
}

// Simulate pagination logic
function applyClientPagination(allGroupedCollections, pageIndex, pageSize) {
    const startIndex = pageIndex * pageSize;
    const endIndex = startIndex + pageSize;

    const paginatedCollections = allGroupedCollections.slice(startIndex, endIndex);
    const totalGroupedTitles = allGroupedCollections.length;
    const currentPage = pageIndex + 1;
    const lastPage = Math.ceil(totalGroupedTitles / pageSize);

    return {
        collections: paginatedCollections,
        currentPage,
        lastPage,
        total: totalGroupedTitles,
        itemsOnCurrentPage: paginatedCollections.length
    };
}

// Run tests
console.log('=== Pagination Test Results ===');
console.log('Original records:', mockRawRecords.length);

const groupedCollections = groupRecordsByTitle(mockRawRecords);
console.log('Grouped collections (unique titles):', groupedCollections.length);

// Test different page sizes
const pageSizes = [3, 6, 9];

pageSizes.forEach(pageSize => {
    console.log(`\n--- Testing with page size: ${pageSize} ---`);

    // Test first page
    const firstPage = applyClientPagination(groupedCollections, 0, pageSize);
    console.log(`Page 1: ${firstPage.itemsOnCurrentPage} items (should be ${Math.min(pageSize, firstPage.total)})`);

    // Test second page if exists
    if (firstPage.lastPage > 1) {
        const secondPage = applyClientPagination(groupedCollections, 1, pageSize);
        console.log(`Page 2: ${secondPage.itemsOnCurrentPage} items`);
    }

    console.log(`Total pages: ${firstPage.lastPage}`);
    console.log(`Total grouped titles: ${firstPage.total}`);

    // Verify that all pages together show all unique titles
    let allItemsCount = 0;
    for (let page = 0; page < firstPage.lastPage; page++) {
        const pageResult = applyClientPagination(groupedCollections, page, pageSize);
        allItemsCount += pageResult.itemsOnCurrentPage;
    }
    console.log(`Verification: All pages combined show ${allItemsCount} items (should equal ${firstPage.total})`);
});

console.log('\n=== Grouped Collections Details ===');
groupedCollections.forEach((group, index) => {
    console.log(`${index + 1}. "${group.title}" - ${group.copy_count} copies`);
});
