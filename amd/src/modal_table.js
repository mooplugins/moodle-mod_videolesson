// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Modal table functionality for videolesson plugin
 *
 * @module     mod_videolesson/modal_table
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalEvents from 'core/modal_events';
import * as Debug from 'mod_videolesson/debug';

/**
 * Filter table rows for modal-based tables.
 * @param {HTMLElement} tableElement - The table element.
 * @param {HTMLElement} inputElement - The input element for filtering.
 * @param {number} columnIndex - The column index to filter.
 */
const filterTableByElement = (tableElement, inputElement, columnIndex) => {
    const filter = inputElement?.value.toLowerCase();
    const rows = tableElement?.getElementsByTagName("tr");

    if (!rows) {
        return;
    }

    for (let i = 1; i < rows.length; i++) { // Skip header row
        const cell = rows[i].getElementsByTagName("td")[columnIndex];
        if (cell) {
            const textValue = cell.textContent || cell.innerText;
            rows[i].style.display = textValue.toLowerCase().includes(filter) ? "" : "none";
        }
    }
};

/**
 * Sort table rows for modal-based tables.
 * @param {HTMLElement} tableElement - The table element.
 * @param {number} columnIndex - The column index to sort.
 */
const sortTableByElement = (tableElement, columnIndex) => {
    const rows = Array.from(tableElement?.rows || []).slice(1); // Get all rows except the header
    if (rows.length === 0) {
        return;
    }

    const isNumeric = !isNaN(rows[0].cells[columnIndex].innerText);
    const sortOrderAttr = `data-sort-order-${columnIndex}`;
    const ascending = tableElement.getAttribute(sortOrderAttr) !== "asc";

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].innerText.toLowerCase();
        const bValue = b.cells[columnIndex].innerText.toLowerCase();

        if (isNumeric) {
            return ascending
                ? parseFloat(aValue) - parseFloat(bValue)
                : parseFloat(bValue) - parseFloat(aValue);
        }
        return ascending
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });

    // Toggle sort order
    tableElement.setAttribute(sortOrderAttr, ascending ? "asc" : "desc");

    const tbody = tableElement.querySelector("tbody");
    if (tbody) {
        tbody.innerHTML = "";
        rows.forEach(row => tbody.appendChild(row));
    }

    updateSortIndicators(tableElement, columnIndex, ascending);
};


/**
 * Apply paging functionality to a table element.
 * @param {HTMLElement} tableElement - The table element.
 * @param {number} rowsPerPage - The number of rows per page.
 */
// eslint-disable-next-line no-unused-vars
const addPagingToTableElement = (tableElement, rowsPerPage = 10) => {
    if (!tableElement) {
        Debug.error("Table element is not provided");
        return;
    }

    const rows = tableElement.getElementsByTagName("tr");
    if (!rows || rows.length <= 1) {
        return;
    }

    const totalRows = rows.length - 1; // Exclude header row
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    let currentPage = 1;

    const renderTablePage = (page) => {
        const start = (page - 1) * rowsPerPage + 1; // Skip header row
        const end = start + rowsPerPage;

        for (let i = 1; i < rows.length; i++) {
            rows[i].style.display = i >= start && i < end ? "" : "none";
        }

        updatePagingControls(tableElement, currentPage, totalPages);
    };

    const createPagingControls = () => {
        const pagingContainer = document.createElement("div");
        pagingContainer.className = "table-paging-controls";
        pagingContainer.style.textAlign = "center";
        pagingContainer.style.marginTop = "10px";

        // Add controls after the table
        tableElement.parentElement.appendChild(pagingContainer);
    };

    const updatePagingControls = (tableElement, pageNum, totalPages) => {
        const pagingContainer = tableElement.parentElement.querySelector(".table-paging-controls");
        pagingContainer.innerHTML = ""; // Clear previous controls

        // Helper function to create page change handler
        const createPageHandler = (newPage) => {
            return () => {
                currentPage = newPage;
                renderTablePage(currentPage);
            };
        };

        // Helper function to create increment/decrement handler
        const createPageIncrementHandler = (delta) => {
            return () => {
                currentPage += delta;
                renderTablePage(currentPage);
            };
        };

        if (pageNum > 1) {
            const prevButton = document.createElement("button");
            prevButton.innerText = "Previous";
            prevButton.onclick = createPageIncrementHandler(-1);
            pagingContainer.appendChild(prevButton);
        }

        for (let i = 1; i <= totalPages; i++) {
            const pageNumber = i; // Capture loop variable in block scope
            const pageButton = document.createElement("button");
            pageButton.innerText = pageNumber;
            pageButton.disabled = pageNumber === pageNum;
            pageButton.onclick = createPageHandler(pageNumber);
            pagingContainer.appendChild(pageButton);
        }

        if (pageNum < totalPages) {
            const nextButton = document.createElement("button");
            nextButton.innerText = "Next";
            nextButton.onclick = createPageIncrementHandler(1);
            pagingContainer.appendChild(nextButton);
        }
    };

    // Initialize paging
    createPagingControls();
    renderTablePage(currentPage);
};

/**
 * Update sort indicators for the table headers.
 * @param {HTMLElement} table - The table element.
 * @param {number} columnIndex - The sorted column index.
 * @param {boolean} ascending - Whether the sort is ascending.
 */
const updateSortIndicators = (table, columnIndex, ascending) => {
    const headers = table.querySelectorAll("th");
    headers.forEach((header, index) => {
        header.setAttribute(
            "data-sort-indicator",
            index === columnIndex ? (ascending ? "▲" : "▼") : ""
        );
    });
};


/**
 * Populate the table with new data.
 * @param {HTMLElement} tableElement - The table element to populate.
 * @param {Array<Object>} data - Array of objects representing table rows.
 * @param {Array<string>} columns - List of keys representing table columns.
 */
// eslint-disable-next-line no-unused-vars
const populateTable = (tableElement, data, columns) => {
    if (!tableElement) {
        Debug.error("Table element is not provided");
        return;
    }

    const tbody = tableElement.querySelector("tbody");
    if (!tbody) {
        Debug.error("Table body element is not found");
        return;
    }

    // Clear the existing table body
    tbody.innerHTML = "";

    // Populate the table with new rows
    data.forEach((row) => {
        const tr = document.createElement("tr");

        columns.forEach((column) => {
            const td = document.createElement("td");
            td.innerText = row[column] || ""; // Use column keys to fetch data
            tr.appendChild(td);
        });

        tbody.appendChild(tr);
    });

    Debug.log("Table repopulated with new data");
};

/**
 * Initialize a modal table for sorting and filtering functionality.
 * @param {object} modal - The modal object (assuming it has `getRoot` method).
 * @param {number} filterColumnIndex - The index of the column for filtering.
 */
export const initModalTable = (modal, filterColumnIndex = 0) => {
    if (!modal) {
        Debug.error('Modal instance is not provided');
        return;
    }

    const $root = modal.getRoot();

    if ($root && $root.length) {
        $root.on(ModalEvents.shown, () => {
            const tableElement = modal.getRoot().find('table').get(0);
            const inputElement = modal.getRoot().find('#videolistsearchinput').get(0);

            if (!tableElement) {
                Debug.error('Table element is not provided or not found');
                return;
            }
            //addPagingToTableElement(tableElement, 2);
            const headers = tableElement.querySelectorAll("th");
            headers.forEach((header, index) => {
                header.addEventListener("click", () => sortTableByElement(tableElement, index));
            });

            if (inputElement) {
                inputElement.addEventListener("input", () =>
                    filterTableByElement(tableElement, inputElement, filterColumnIndex)
                );
            }

            tableElement.addEventListener('click', (event) => {
                const row = event.target.closest('tr');
                if (!row || !tableElement.contains(row)) {
                    return;
                }
                const hash = row.dataset.contenthash;
                const title = row.dataset.title;
                if (hash) {
                    const rows = tableElement.querySelectorAll('tbody tr');
                    rows.forEach(r => r.classList.remove('selected'));
                    row.classList.add('selected');
                    tableElement.dataset.selected = hash;
                    tableElement.dataset.title = title;
                } else {
                    Debug.warn('No content hash found for the selected row');
                }
            });
        });
    } else {
        Debug.error('Modal root element not found or empty');
    }
};
