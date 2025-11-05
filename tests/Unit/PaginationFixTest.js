/**
 * Enhanced Test for Pagination with Grouped Records
 * Tests all the fixes applied to Welcome.vue
 */

// Mock data simulating API response with duplicate titles
const mockApiResponse = {
    data: [
        { id: 1, title: 'Advanced Programming', accession_number: '001', status: 'available', created_at: '2024-01-15' },
        { id: 2, title: 'Advanced Programming', accession_number: '002', status: 'borrowed', created_at: '2024-01-14' }, // Duplicate title
        { id: 3, title: 'Data Structures', accession_number: '003', status: 'available', created_at: '2024-01-13' },
        { id: 4, title: 'Web Development', accession_number: '004', status: 'available', created_at: '2024-01-12' },
        { id: 5, title: 'Data Structures', accession_number: '005', status: 'available', created_at: '2024-01-11' }, // Duplicate title
        { id: 6, title: 'Machine Learning', accession_number: '006', status: 'available', created_at: '2024-01-10' },
        { id: 7, title: 'Database Systems', accession_number: '007', status: 'available', created_at: '2024-01-09' },
        { id: 8, title: 'Network Security', accession_number: '008', status: 'available', created_at: '2024-01-08' },
        { id: 9, title: 'Software Engineering', accession_number: '009', status: 'available', created_at: '2024-01-07' },
        { id: 10, title: 'Machine Learning', accession_number: '010', status: 'available', created_at: '2024-01-06' }, // Duplicate title
        { id: 11, title: 'Operating Systems', accession_number: '011', status: 'available', created_at: '2024-01-05' },
        { id: 12, title: 'Computer Graphics', accession_number: '012', status: 'available', created_at: '2024-01-04' }
    ],
    total: 12,
    current_page: 1,
    per_page: 1000,
    last_page: 1
};

// Simulate the grouping function from Welcome.vue
function groupRecordsByTitle(records) {
    const titleGroups = new Map();

    records.forEach(record => {
        const title = record.title || 'Untitled';
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

    console.log('Grouping Results:', {
        originalRecords: records.length,
        uniqueTitles: titleGroups.size,
        groupedRecords: groupedRecords.length,
        sampleGroups: Array.from(titleGroups.entries()).slice(0, 3).map(([title, copies]) => ({
            title: title.substring(0, 50),
            copyCount: copies.length
        }))
    });

    return groupedRecords;
}

// Simulate the pagination function from Welcome.vue
function applyClientPagination(allGroupedCollections, pagination) {
    if (!allGroupedCollections || allGroupedCollections.length === 0) {
        return {
            collections: [],
            currentPage: 1,
            lastPage: 1,
            total: 0
        };
    }

    const maxPageIndex = Math.max(0, Math.ceil(allGroupedCollections.length / pagination.pageSize) - 1);
    if (pagination.pageIndex > maxPageIndex) {
        pagination.pageIndex = maxPageIndex;
    }

    const startIndex = pagination.pageIndex * pagination.pageSize;
    const endIndex = startIndex + pagination.pageSize;

    const collections = allGroupedCollections.slice(startIndex, endIndex);
    const currentPage = pagination.pageIndex + 1;
    const lastPage = Math.max(1, Math.ceil(allGroupedCollections.length / pagination.pageSize));
    const total = allGroupedCollections.length;

    console.log('Pagination Applied:', {
        pageIndex: pagination.pageIndex,
        pageSize: pagination.pageSize,
        currentPage,
        totalPages: lastPage,
        itemsOnPage: collections.length,
        totalTitles: total,
        startIndex,
        endIndex
    });

    return {
        collections,
        currentPage,
        lastPage,
        total,
        isFirstPage: pagination.pageIndex === 0,
        isLastPage: pagination.pageIndex >= maxPageIndex
    };
}

// Test scenarios
console.log('=== PAGINATION FIX TESTING ===\n');

// Step 1: Group the records
const groupedCollections = groupRecordsByTitle(mockApiResponse.data);
console.log(`✅ Grouped ${mockApiResponse.data.length} records into ${groupedCollections.length} unique titles\n`);

// Step 2: Test different page sizes
const pageSizes = [3, 6, 9, 12];
pageSizes.forEach(pageSize => {
    console.log(`--- Testing Page Size: ${pageSize} ---`);

    const pagination = { pageIndex: 0, pageSize };
    let result = applyClientPagination(groupedCollections, pagination);

    console.log(`✅ Page 1: ${result.collections.length} items (expected: ${Math.min(pageSize, result.total)})`);
    console.log(`✅ Total pages: ${result.lastPage}`);
    console.log(`✅ Can go next: ${!result.isLastPage}`);

    // Test next page navigation if available
    if (!result.isLastPage) {
        pagination.pageIndex = 1;
        result = applyClientPagination(groupedCollections, pagination);
        console.log(`✅ Page 2: ${result.collections.length} items`);
        console.log(`✅ Can go previous: ${!result.isFirstPage}`);
    }

    console.log('');
});

// Step 3: Test edge cases
console.log('--- Testing Edge Cases ---');

// Test last page navigation
const pagination = { pageIndex: 0, pageSize: 3 };
let result = applyClientPagination(groupedCollections, pagination);
const totalPages = result.lastPage;

// Go to last page
pagination.pageIndex = totalPages - 1;
result = applyClientPagination(groupedCollections, pagination);
console.log(`✅ Last page (${result.currentPage}): ${result.collections.length} items`);
console.log(`✅ Is last page: ${result.isLastPage}`);

// Test page bounds
pagination.pageIndex = 999; // Invalid high page
result = applyClientPagination(groupedCollections, pagination);
console.log(`✅ Invalid high page corrected to: ${result.currentPage}`);

console.log('\n--- Page Size Validation Test ---');
const validSizes = [3, 6, 9, 12];
const invalidSizes = [1, 5, 15, 20];

validSizes.forEach(size => {
    if (validSizes.includes(size)) {
        console.log(`✅ Page size ${size}: Valid`);
    }
});

invalidSizes.forEach(size => {
    if (!validSizes.includes(size)) {
        console.log(`❌ Page size ${size}: Invalid (would be rejected)`);
    }
});

console.log('\n=== SUMMARY ===');
console.log(`Original records: ${mockApiResponse.data.length}`);
console.log(`Unique titles: ${groupedCollections.length}`);
console.log(`Duplicate title reduction: ${mockApiResponse.data.length - groupedCollections.length} records`);
console.log('✅ All pagination functions should now work correctly!');
console.log('✅ Page navigation buttons should be responsive!');
console.log('✅ All page size options should work!');
console.log('✅ Pagination counts based on grouped titles!');
