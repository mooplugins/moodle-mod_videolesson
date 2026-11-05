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
 * Table functionality for videolesson plugin
 *
 * @module     mod_videolesson/table
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const initializedTables = {};

/**
 * Update visible video count label after filtering.
 *
 * @param {HTMLElement} table
 */
const updateVisibleCount = (table) => {
    const picker = table.closest('.videolist-picker');
    const countEl = picker ? picker.querySelector('[data-videolist-count]') : null;
    if (!countEl) {
        return;
    }

    const rows = table.querySelectorAll('tbody tr');
    let visible = 0;
    rows.forEach((row) => {
        if (row.style.display !== 'none' && !row.classList.contains('videolist-row--hidden')) {
            visible++;
        }
    });

    const one = countEl.dataset.countOne || '1 video found';
    const manyTemplate = countEl.dataset.countTemplate || 'COUNT videos found';
    countEl.textContent = visible === 1
        ? one
        : manyTemplate.replace('COUNT', String(visible));
};

/**
 * Filter table rows by title/folder text (and data-title).
 *
 * @param {string} tableId
 * @param {string} inputId
 */
const filterTable = (tableId, inputId) => {
    const input = document.getElementById(inputId);
    const filter = (input?.value || '').toLowerCase().trim();
    const table = document.getElementById(tableId);
    const rows = table?.querySelectorAll('tbody tr');

    if (!rows) {
        return;
    }

    rows.forEach((row) => {
        const title = (row.getAttribute('data-title') || '').toLowerCase();
        const folderCell = row.querySelector('.videolist-col-folder, td:first-child');
        const titleCell = row.querySelector('.videolist-col-title');
        const folderText = (folderCell?.textContent || '').toLowerCase();
        const titleText = (titleCell?.textContent || '').toLowerCase();
        const haystack = `${title} ${folderText} ${titleText}`;
        const match = !filter || haystack.includes(filter);
        // Must use a class — row CSS uses display:grid !important which beats inline style.
        row.classList.toggle('videolist-row--hidden', !match);
    });

    updateVisibleCount(table);
};

/**
 * Sort body rows by column index.
 *
 * @param {string} tableId
 * @param {number} columnIndex
 * @param {HTMLElement|null} headerRow
 */
const sortTable = (tableId, columnIndex, headerRow = null) => {
    const table = document.getElementById(tableId);
    const rows = Array.from(table?.querySelectorAll('tbody tr') || []);
    if (rows.length === 0) {
        return;
    }

    const isNumeric = !isNaN(rows[0].cells[columnIndex]?.innerText);
    const sortOrderAttr = `data-sort-order-${columnIndex}`;
    const ascending = table.getAttribute(sortOrderAttr) !== 'asc';

    rows.sort((a, b) => {
        const aValue = (a.cells[columnIndex]?.innerText || '').toLowerCase();
        const bValue = (b.cells[columnIndex]?.innerText || '').toLowerCase();

        if (isNumeric) {
            return ascending
                ? parseFloat(aValue) - parseFloat(bValue)
                : parseFloat(bValue) - parseFloat(aValue);
        }
        return ascending
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });

    table.setAttribute(sortOrderAttr, ascending ? 'asc' : 'desc');

    const tbody = table.querySelector('tbody');
    if (tbody) {
        rows.forEach((row) => tbody.appendChild(row));
    }

    const headers = headerRow
        ? headerRow.querySelectorAll('[data-sort-col]')
        : table.querySelectorAll('th');
    headers.forEach((header) => {
        const index = parseInt(header.getAttribute('data-sort-col') ?? '-1', 10);
        header.setAttribute(
            'data-sort-indicator',
            index === columnIndex ? (ascending ? '▲' : '▼') : ''
        );
    });
};

/**
 * Initialize a table for sorting and filtering functionality.
 * @param {string} tableId - The ID of the table.
 * @param {string} inputId - The ID of the input field for filtering.
 * @param {number} filterColumnIndex - Unused (kept for call-site compatibility).
 */
export const initTable = (tableId = 'videolist', inputId = 'videolistsearchinput', filterColumnIndex = 2) => {
    void filterColumnIndex;

    if (initializedTables[tableId]) {
        return;
    }

    const table = document.getElementById(tableId);
    if (!table) {
        return;
    }

    const picker = table.closest('.videolist-picker');
    const headerRow = picker ? picker.querySelector('.videolist-row--head') : null;
    const sortableHeaders = headerRow
        ? headerRow.querySelectorAll('[data-sort-col]')
        : table.querySelectorAll('th');

    sortableHeaders.forEach((header) => {
        if (header.classList.contains('videolist-col-status')
                || header.classList.contains('videolist-col-thumb')) {
            return;
        }
        const columnIndex = parseInt(header.getAttribute('data-sort-col') || '0', 10);
        const onSort = () => sortTable(tableId, columnIndex, headerRow);
        header.addEventListener('click', onSort);
        header.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                onSort();
            }
        });
    });

    const input = document.getElementById(inputId);
    if (input) {
        input.addEventListener('input', () => filterTable(tableId, inputId));
    }

    initializedTables[tableId] = true;
};
