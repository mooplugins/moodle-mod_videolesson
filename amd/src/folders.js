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
 * Folder tree management and drag-and-drop functionality
 *
 * @module     mod_videolesson/folders
 * @copyright  2022-2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import * as Toast from 'core/toast';
import Config from 'core/config';

let selectedFolderId = 0;
let folderTree = [];
let bbbFolderTree = [];
const folderInputName = 'foldername';
const folderInputId = 'videolesson-folder-name';

const resolveFolderId = (value) => {
    if (value === 'bbb') {
        return 'bbb';
    }
    if (value === null || value === 'null' || value === 'uncategorized') {
        return null;
    }
    if (value === undefined || value === '' || value === 'all') {
        return 0;
    }
    const parsed = parseInt(value, 10);
    return Number.isNaN(parsed) ? 0 : parsed;
};

/**
 * Render folder input field for modal dialogs.
 *
 * @param {String} label Input label text
 * @param {String} value Default value
 * @return {Promise<String>} Rendered HTML
 */
const renderFolderInput = (label, value = '') => Templates.render('mod_videolesson/folder_modal_form', {
    elementid: folderInputId,
    label: label,
    name: folderInputName,
    value: value,
    required: true,
});

/**
 * Initialize folder tree
 * @param {number|null} initialFolder Initial folder ID
 */
export const init = (initialFolder = null) => {
    if (initialFolder !== null && typeof initialFolder !== 'undefined') {
        selectedFolderId = resolveFolderId(initialFolder);
    } else {
        const treeContainer = document.querySelector('.videolesson-folder-tree-container');
        if (treeContainer && treeContainer.dataset.selectedFolder !== undefined) {
            selectedFolderId = resolveFolderId(treeContainer.dataset.selectedFolder);
        }
    }
    loadFolderTree();
    setupEventListeners();
    setupDragAndDrop();

    document.addEventListener('videolesson:set-folder', (e) => {
        if (e.detail && typeof e.detail.folderId !== 'undefined') {
            selectFolder(resolveFolderId(e.detail.folderId), false);
        }
    });
};

/**
 * Load folder tree from server
 * @returns {Promise<void>}
 */
export const loadFolderTree = async() => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_videolesson_get_folder_tree',
            args: {}
        }])[0];

        folderTree = response.folders || [];
        bbbFolderTree = response.bbbfolders || [];
        const treeEl = document.querySelector('.videolesson-folder-tree-container');
        // Prefer webservice flag; fall back to the server-rendered data attribute if the
        // response is missing the field (e.g. stale service definition / opcache).
        let bbbAvailable = response.bbbavailable;
        if (typeof bbbAvailable === 'undefined' || bbbAvailable === null) {
            bbbAvailable = treeEl && treeEl.dataset.bbbAvailable === '1';
        }
        renderFolderTree(
            folderTree,
            response.uncategorizedcount,
            response.totalcount,
            !!bbbAvailable,
            response.bbbcount || 0,
            bbbFolderTree
        );
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Render folder tree
 *
 * @param {Array} folders Array of folder objects
 * @param {number} uncategorizedCount Number of uncategorized videos
 * @param {number} totalCount Total count of all videos
 * @param {boolean} bbbAvailable Whether BBB folder is available
 * @param {number} bbbCount Number of BBB recordings in BBB root
 * @param {Array} bbbFolders BBB-scoped subfolders
 */
const renderFolderTree = async(
    folders,
    uncategorizedCount = 0,
    totalCount = 0,
    bbbAvailable = false,
    bbbCount = 0,
    bbbFolders = []
) => {
    try {
        const preparedBbb = prepareFolderTreeData(bbbFolders || []);
        const context = {
            folders: prepareFolderTreeData(folders),
            bbb_folders: preparedBbb,
            has_bbb_folders: preparedBbb.length > 0,
            selected_folder: {
                is_all: selectedFolderId === 0,
                is_root: selectedFolderId === 0,
                is_uncategorized: selectedFolderId === null,
                is_bbb: selectedFolderId === 'bbb',
                value: selectedFolderId === null ? 'uncategorized' : String(selectedFolderId === 0 ? 'all' : selectedFolderId)
            },
            can_manage: true,
            uncategorized_count: uncategorizedCount,
            totalcount: totalCount,
            bbb_available: bbbAvailable ? 1 : 0,
            bbb_count: Number(bbbCount) || 0,
            // Only needed when BBB folder is shown; fall back to videolesson icon if BBB plugin files are gone.
            bbb_icon_url: Config.wwwroot + (
                bbbAvailable
                    ? '/mod/bigbluebuttonbn/pix/monologo.svg'
                    : '/mod/videolesson/pix/monologo.svg'
            )
        };

        const html = await Templates.render('mod_videolesson/folder_tree', context);
        const container = document.querySelector('.videolesson-folder-tree-container');
        if (container) {
            // Replace the whole container (not innerHTML) so we do not nest wrappers.
            container.outerHTML = html;
            const newContainer = document.querySelector('.videolesson-folder-tree-container');
            if (newContainer) {
                newContainer.dataset.selectedFolder = selectedFolderId === null ? 'uncategorized'
                    : (selectedFolderId === 0 ? 'all' : String(selectedFolderId));
            }
            setupFolderInteractions();
            updateCollapseAllButtonState();
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Prepare folder tree data for template (recursive)
 *
 * @param {Array} folders Array of folder objects
 * @return {Array} Prepared folder data
 */
const prepareFolderTreeData = (folders) => {
    return (folders || []).map(folder => {
        const hasChildren = folder.children && folder.children.length > 0;
        const canCreateSubfolder = typeof folder.can_create_subfolder !== 'undefined'
            ? !!folder.can_create_subfolder
            : folder.depth < 2; // Max depth is 3, so can create if depth < 2
        const preparedFolder = {
            ...folder,
            has_children: hasChildren,
            can_create_subfolder: canCreateSubfolder,
            can_manage: true,
            is_bbb_scope: !!(folder.is_bbb_scope || folder.scope === 'bbb'),
            selected: (selectedFolderId !== null) && (selectedFolderId !== 'bbb')
                && (parseInt(folder.id, 10) === parseInt(selectedFolderId, 10))
        };

        // Recursively prepare children
        if (hasChildren) {
            preparedFolder.children = prepareFolderTreeData(folder.children);
        }

        return preparedFolder;
    });
};

/**
 * Setup event listeners
 */
const setupEventListeners = () => {
    document.body.addEventListener('click', (e) => {
        // Handle action buttons first (before folder selection to prevent interference)
        // Rename folder button (check for button or icon inside)
        const renameButton = e.target.closest('.videolesson-rename-folder');
        if (renameButton) {
            e.preventDefault();
            e.stopPropagation(); // Prevent folder selection from firing
            const folderIdAttr = renameButton.getAttribute('data-folder-id') || renameButton.dataset.folderId;
            const folderId = parseInt(folderIdAttr, 10);
            if (isNaN(folderId) || folderId <= 0) {
                Notification.exception(new Error('Invalid folder ID: ' + folderIdAttr));
                return;
            }
            showRenameFolderDialog(folderId).catch(error => {
                Notification.exception(error);
            });
            return;
        }

        // Delete folder button
        if (e.target.closest('.videolesson-delete-folder')) {
            e.preventDefault();
            e.stopPropagation(); // Prevent folder selection from firing
            const button = e.target.closest('.videolesson-delete-folder');
            const folderId = parseInt(button.dataset.folderId);
            showDeleteFolderDialog(folderId);
            return;
        }

        // Create folder button
        if (e.target.closest('.videolesson-create-folder, .videolesson-create-subfolder')) {
            e.preventDefault();
            e.stopPropagation(); // Prevent folder selection from firing
            const button = e.target.closest('.videolesson-create-folder, .videolesson-create-subfolder');
            const parentId = button.dataset.parentId || null;
            showCreateFolderDialog(parentId);
            return;
        }

        // Folder toggle (expand/collapse) - check this BEFORE collapse all to prevent conflicts
        if (e.target.closest('.folder-toggle')) {
            e.preventDefault();
            e.stopPropagation(); // Prevent folder selection from firing
            const button = e.target.closest('.folder-toggle');
            toggleFolder(button);
            // Update collapse all button state after individual folder toggle
            updateCollapseAllButtonState();
            return;
        }

        // Collapse all folders - must check that we're NOT clicking on a folder toggle
        const collapseAllLink = e.target.closest('.videolesson-collapse-all');
        if (collapseAllLink && !e.target.closest('.folder-toggle')) {
            e.preventDefault();
            e.stopPropagation();
            collapseAllFolders();
            return;
        }

        // Folder selection (only if not clicking on action buttons)
        if (e.target.closest('.videolesson-folder-item')) {
            const folderItem = e.target.closest('.videolesson-folder-item');
            // Don't select if clicking on folder-actions or any button inside
            if (!e.target.closest('.folder-actions') &&
                !e.target.closest('button') &&
                !e.target.closest('a')) {
                const folderId = resolveFolderId(folderItem.dataset.folderId);
                selectFolder(folderId);
            }
            e.stopPropagation();
        }
    });
};

/**
 * Setup drag and drop
 */
const setupDragAndDrop = () => {
    let isDragging = false;
    let dragLeaveTimeout = null;

    // Helper to get folder sidebar/container element
    const getFolderContainer = () => {
        return document.querySelector('.videolesson-folder-list');
    };

    // Helper to update sidebar drag-active state
    const updateSidebarDragState = (active) => {
        const container = getFolderContainer();
        if (container) {
            if (active) {
                container.classList.add('drag-active');
            } else {
                container.classList.remove('drag-active');
            }
        }
    };

    // Make video table rows draggable via handle only.
    document.body.addEventListener('dragstart', (e) => {
        const handle = e.target.closest('.videolesson-drag-handle');
        if (!handle) {
            return;
        }

        const row = handle.closest('tr[data-videolesson-id]');
        if (!row) {
            return;
        }

        const isBbb = row.dataset.bbb === '1';
        const recordingId = row.dataset.recordingId || '';
        const videoId = parseInt(row.dataset.videolessonId, 10);

        if (isBbb && recordingId) {
            e.dataTransfer.setData('bbb-recording-id', recordingId);
            e.dataTransfer.setData('drag-type', 'bbb');
        } else {
            e.dataTransfer.setData('videolesson-id', String(videoId));
            e.dataTransfer.setData('drag-type', 'library');
        }
        e.dataTransfer.effectAllowed = 'move';

        // ---- Create drag image from full row ----
        const dragPreview = row.cloneNode(true);
        dragPreview.style.position = 'absolute';
        dragPreview.style.top = '-9999px';
        dragPreview.style.left = '-9999px';
        dragPreview.style.width = row.offsetWidth + 'px';
        dragPreview.style.background = '#fff';
        dragPreview.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        dragPreview.style.opacity = '0.95';

        document.body.appendChild(dragPreview);

        e.dataTransfer.setDragImage(dragPreview, 20, 20);

        // Remove after drag starts
        setTimeout(() => {
            document.body.removeChild(dragPreview);
        }, 0);

        row.classList.add('dragging');
        isDragging = true;
        updateSidebarDragState(true);
    });

    document.body.addEventListener('dragend', (e) => {
        const row = e.target.closest('tr[data-videolesson-id]');
        if (row) {
            row.classList.remove('dragging');
        }
        document.querySelectorAll('.videolesson-folder-item.drag-over').forEach(item => {
            item.classList.remove('drag-over');
        });
        // Remove sidebar highlight when drag ends
        updateSidebarDragState(false);
        isDragging = false;
        if (dragLeaveTimeout) {
            clearTimeout(dragLeaveTimeout);
            dragLeaveTimeout = null;
        }
    });

    const isValidDropTarget = (folderItem, dragType) => {
        if (!folderItem || folderItem.dataset.droppable !== 'true') {
            return false;
        }
        const scope = folderItem.dataset.scope || 'library';
        const folderId = folderItem.dataset.folderId;
        if (dragType === 'bbb') {
            return scope === 'bbb' || folderId === 'bbb';
        }
        // Library videos: never drop on BBB tree.
        if (scope === 'bbb' || folderId === 'bbb') {
            return false;
        }
        return true;
    };

    // Folder drop zones
    document.body.addEventListener('dragover', (e) => {
        if (!isDragging) {
            return;
        }
        const types = Array.from(e.dataTransfer.types || []);
        const dragType = types.includes('bbb-recording-id') ? 'bbb'
            : (types.includes('videolesson-id') ? 'library' : null);
        if (!dragType) {
            return;
        }

        const folderItem = e.target.closest('.videolesson-folder-item[data-droppable="true"]');
        if (folderItem && isValidDropTarget(folderItem, dragType)) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            folderItem.classList.add('drag-over');
            // Remove sidebar highlight when over a specific folder item
            updateSidebarDragState(false);
            if (dragLeaveTimeout) {
                clearTimeout(dragLeaveTimeout);
                dragLeaveTimeout = null;
            }
        } else {
            if (folderItem) {
                folderItem.classList.remove('drag-over');
            }
            // If not over a folder item, ensure sidebar is highlighted
            const container = getFolderContainer();
            if (container && container.contains(e.target)) {
                updateSidebarDragState(true);
            }
        }
    });

    document.body.addEventListener('dragleave', (e) => {
        if (!isDragging) {
            return;
        }

        const folderItem = e.target.closest('.videolesson-folder-item[data-droppable="true"]');
        if (folderItem) {
            folderItem.classList.remove('drag-over');
            // Use timeout to check if we're leaving to go to another folder item
            // or leaving the folder area entirely
            dragLeaveTimeout = setTimeout(() => {
                const relatedTarget = e.relatedTarget;
                const container = getFolderContainer();
                if (container && (!relatedTarget || !container.contains(relatedTarget))) {
                    // Not entering another folder item, highlight sidebar
                    updateSidebarDragState(true);
                }
            }, 10);
        }
    });

    document.body.addEventListener('drop', async (e) => {
        const folderItem = e.target.closest('.videolesson-folder-item[data-droppable="true"]');
        if (!folderItem) {
            return;
        }

        const recordingId = e.dataTransfer.getData('bbb-recording-id');
        const videoIdRaw = e.dataTransfer.getData('videolesson-id');
        const dragType = recordingId ? 'bbb' : (videoIdRaw ? 'library' : null);
        if (!dragType || !isValidDropTarget(folderItem, dragType)) {
            return;
        }

        e.preventDefault();
        folderItem.classList.remove('drag-over');
        updateSidebarDragState(false);

        const rawFolderId = folderItem.dataset.folderId;
        if (dragType === 'bbb') {
            const folderId = rawFolderId === 'bbb' ? 'bbb' : resolveFolderId(rawFolderId);
            await moveBbbRecording(recordingId, folderId);
        } else {
            if (rawFolderId === 'bbb') {
                return;
            }
            const folderId = resolveFolderId(rawFolderId);
            await moveVideo(parseInt(videoIdRaw, 10), folderId);
        }

        if (dragLeaveTimeout) {
            clearTimeout(dragLeaveTimeout);
            dragLeaveTimeout = null;
        }
    });
};

/**
 * Setup folder interactions after render
 */
const setupFolderInteractions = () => {
    // Restore expanded state if needed
    // Add any additional setup here
};

/**
 * Toggle folder expand/collapse
 *
 * @param {HTMLElement} button Toggle button element
 */
const toggleFolder = (button) => {
    const folderItem = button.closest('.videolesson-folder-item');
    const children = folderItem.querySelector('.folder-children');
    const icon = button.querySelector('i');

    if (children) {
        const isExpanded = children.classList.contains('expanded');
        if (isExpanded) {
            // Collapse
            children.classList.remove('expanded');
            button.setAttribute('aria-expanded', 'false');
            if (icon) {
                icon.style.transform = 'rotate(0deg)';
            }
        } else {
            // Expand
            children.classList.add('expanded');
            button.setAttribute('aria-expanded', 'true');
            if (icon) {
                icon.style.transform = 'rotate(90deg)';
            }
        }
    }
};

/**
 * Update the collapse/expand all button state based on current folder tree state
 */
const updateCollapseAllButtonState = () => {
    const tree = document.querySelector('.videolesson-folder-tree-container');
    const collapseAllButton = document.querySelector('.videolesson-collapse-all');

    if (!tree || !collapseAllButton) {
        return;
    }

    const allToggleButtons = tree.querySelectorAll('.folder-toggle');
    if (allToggleButtons.length === 0) {
        return;
    }

    // Check current state: count how many are expanded vs collapsed
    let expandedCount = 0;
    let collapsedCount = 0;

    allToggleButtons.forEach((button) => {
        const folderItem = button.closest('.videolesson-folder-item');
        if (!folderItem) {
            return;
        }

        const children = folderItem.querySelector('.folder-children');
        if (children) {
            const isExpanded = children.classList.contains('expanded');
            if (isExpanded) {
                expandedCount++;
            } else {
                collapsedCount++;
            }
        }
    });

    // Determine if button should show expand or collapse
    const shouldShowExpand = expandedCount === 0 || expandedCount < collapsedCount;
    const collapseIcon = collapseAllButton.querySelector('i');

    if (collapseIcon) {
        if (shouldShowExpand) {
            collapseIcon.classList.remove('fa-down-left-and-up-right-to-center', 'fa-compress');
            collapseIcon.classList.add('fa-up-right-and-down-left-from-center');
            getString('folder:expand_all', 'mod_videolesson').then((expandAllText) => {
                collapseAllButton.setAttribute('title', expandAllText);
                collapseAllButton.setAttribute('aria-label', expandAllText);
            });
        } else {
            collapseIcon.classList.remove('fa-up-right-and-down-left-from-center', 'fa-expand');
            collapseIcon.classList.add('fa-down-left-and-up-right-to-center');
            getString('folder:collapse_all', 'mod_videolesson').then((collapseAllText) => {
                collapseAllButton.setAttribute('title', collapseAllText);
                collapseAllButton.setAttribute('aria-label', collapseAllText);
            });
        }
    }
};

/**
 * Toggle expand/collapse all folders in the tree
 */
const collapseAllFolders = () => {
    const tree = document.querySelector('.videolesson-folder-tree-container');
    if (!tree) {
        return;
    }

    const allToggleButtons = tree.querySelectorAll('.folder-toggle');
    if (allToggleButtons.length === 0) {
        return;
    }

    // Check current state: count how many are expanded vs collapsed
    let expandedCount = 0;
    let collapsedCount = 0;

    allToggleButtons.forEach((button) => {
        const folderItem = button.closest('.videolesson-folder-item');
        if (!folderItem) {
            return;
        }

        const children = folderItem.querySelector('.folder-children');
        if (children) {
            const isExpanded = children.classList.contains('expanded');
            if (isExpanded) {
                expandedCount++;
            } else {
                collapsedCount++;
            }
        }
    });

    // Determine action: if more expanded than collapsed, collapse all; otherwise expand all
    const shouldExpand = expandedCount === 0 || expandedCount < collapsedCount;

    // Apply the action
    allToggleButtons.forEach((button) => {
        const folderItem = button.closest('.videolesson-folder-item');
        if (!folderItem) {
            return;
        }

        const children = folderItem.querySelector('.folder-children');
        const icon = button.querySelector('i');

        if (children) {
            if (shouldExpand) {
                // Expand all
                children.classList.add('expanded');
                button.setAttribute('aria-expanded', 'true');
                if (icon) {
                    icon.style.transform = 'rotate(90deg)';
                }
            } else {
                // Collapse all
                children.classList.remove('expanded');
                button.setAttribute('aria-expanded', 'false');
                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        }
    });

    // Update the button state after toggling
    updateCollapseAllButtonState();
};

/**
 * Update UI and optionally dispatch selection event.
 *
 * @param {number|null} folderId
 * @param {boolean} triggerEvent
 */
const selectFolder = (folderId, triggerEvent = true) => {
    selectedFolderId = resolveFolderId(folderId);

    // Update UI
    document.querySelectorAll('.videolesson-folder-item').forEach(item => {
        item.classList.remove('selected');
    });

    let selectorValue;
    if (selectedFolderId === null) {
        selectorValue = 'uncategorized';
    } else if (selectedFolderId === 0) {
        selectorValue = 'all';
    } else {
        selectorValue = String(selectedFolderId);
    }
    const selectedItem = document.querySelector(`[data-folder-id="${selectorValue}"]`);
    if (selectedItem) {
        selectedItem.classList.add('selected');
    }

    if (triggerEvent) {
        const event = new CustomEvent('videolesson:folder:selected', {
            detail: {folderId: selectedFolderId}
        });
        document.dispatchEvent(event);
    }
};

/**
 * Show create folder dialog
 *
 * @param {number|null} parentId Parent folder ID
 */
const showCreateFolderDialog = async (parentId) => {
    const strings = await Promise.all([
        getString('folder:create', 'mod_videolesson'),
        getString('folder:name', 'mod_videolesson'),
        getString('cancel'),
        getString('create')
    ]);

    try {
        const body = await renderFolderInput(strings[1]);
        const modal = await ModalSaveCancel.create({
            title: strings[0],
            body: body,
            buttons: {
                cancel: strings[2],
                save: strings[3],
            },
            removeOnClose: true,
        });

        modal.getRoot().on(ModalEvents.save, async () => {
            const name = modal.getRoot().find(`input[name="${folderInputName}"]`).val();
            if (name && name.trim()) {
                await createFolder(name.trim(), parentId);
                modal.destroy();
            }
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Recursively find a folder in the tree by ID
 *
 * @param {Array} folders Array of folder objects to search
 * @param {number} folderId Folder ID to find
 * @return {Object|null} Found folder object or null
 */
const findFolderInTree = (folders, folderId) => {
    for (const folder of folders) {
        if (parseInt(folder.id) === parseInt(folderId)) {
            return folder;
        }
        if (folder.children && folder.children.length > 0) {
            const found = findFolderInTree(folder.children, folderId);
            if (found) {
                return found;
            }
        }
    }
    return null;
};

/**
 * Check if a folder is the excluded folder itself
 *
 * @param {Object} folder Folder to check
 * @param {number} excludeFolderId Folder ID to exclude
 * @return {boolean} True if folder is the excluded folder
 */
const isExcludedFolder = (folder, excludeFolderId) => {
    return parseInt(folder.id, 10) === parseInt(excludeFolderId, 10);
};

/**
 * Check if a folder is a descendant of the excluded folder
 * (used to skip children of the excluded folder)
 *
 * @param {Object} folder Folder to check
 * @param {number} excludeFolderId Folder ID to exclude
 * @return {boolean} True if folder is a descendant of the excluded folder
 */
// const isDescendantOf = (folder, excludeFolderId) => {
//     // This function is only called when processing children of folders
//     // It checks if the current folder is the excluded folder or a descendant
//     if (parseInt(folder.id, 10) === parseInt(excludeFolderId, 10)) {
//         return true;
//     }
//     if (folder.children && folder.children.length > 0) {
//         return folder.children.some(child => isDescendantOf(child, excludeFolderId));
//     }
//     return false;
// };

/**
 * Build folder options for select dropdown, excluding current folder and its descendants
 *
 * @param {Array} folders Folder tree array
 * @param {number} excludeFolderId Folder ID to exclude (and its descendants)
 * @param {number|null} currentParentId Current parent ID to mark as selected
 * @param {number} depth Current depth level
 * @return {Array} Array of option objects {value, label, selected}
 */
const buildFolderOptions = (folders, excludeFolderId, currentParentId = null, depth = 0) => {
    const options = [];
    const indent = depth > 0 ? '— '.repeat(depth) : '';

    // Add "Root" option (no parent)
    if (depth === 0) {
        // Normalize currentParentId for comparison
        const isNullParent = currentParentId === null ||
            currentParentId === undefined ||
            currentParentId === 0 ||
            currentParentId === '0';
        const normalizedCurrentParent = isNullParent ? null : parseInt(currentParentId, 10);
        const rootSelected = normalizedCurrentParent === null;
        options.push({
            value: '0',
            label: 'Root',
            selected: rootSelected
        });
    }

    for (const folder of folders) {
        const folderId = parseInt(folder.id, 10);
        const excludeId = parseInt(excludeFolderId, 10);

        // Skip only if this folder IS the excluded folder itself
        if (isExcludedFolder(folder, excludeId)) {
            // Don't add this folder, but we can still process its siblings
            continue;
        }

        // Skip folders at depth 2 or higher (max depth is 3, so depth 2 cannot be a parent)
        if (folder.depth >= 2) {
            // Don't add this folder as a parent option, but we can still process its children
            // (though they would be depth 3 which shouldn't exist)
            continue;
        }

        // Normalize both IDs for comparison
        const isNullParent = currentParentId === null ||
            currentParentId === 0 ||
            currentParentId === '0';
        const normalizedCurrentParent = isNullParent ? null : parseInt(currentParentId, 10);
        const isSelected = normalizedCurrentParent !== null && normalizedCurrentParent === folderId;
        options.push({
            value: folderId.toString(),
            label: indent + folder.name,
            selected: isSelected
        });

        // Recursively add children, but skip the excluded folder and its descendants
        if (folder.children && folder.children.length > 0) {
            const childOptions = buildFolderOptions(folder.children, excludeFolderId, currentParentId, depth + 1);
            options.push(...childOptions);
        }
    }

    return options;
};

/**
 * Show edit folder dialog (rename and change parent)
 *
 * @param {number} folderId Folder ID to edit
 */
const showRenameFolderDialog = async (folderId) => {
    const folder = findFolderInTree(folderTree, folderId) || findFolderInTree(bbbFolderTree, folderId);
    if (!folder) {
        Toast.add('Folder not found', {type: 'error'});
        return;
    }

    const strings = await Promise.all([
        getString('folder:edit', 'mod_videolesson').catch(() => getString('folder:rename', 'mod_videolesson')),
        getString('folder:name', 'mod_videolesson'),
        getString('folder:select', 'mod_videolesson'),
        getString('cancel'),
        getString('savechanges')
    ]);

    try {
        // Normalize current parent ID for comparison
        // Handle both null and numeric parent IDs from the tree structure
        let currentParentId = folder.parent;
        const isNullParent = currentParentId === null ||
            currentParentId === undefined ||
            currentParentId === 0 ||
            currentParentId === '0' ||
            currentParentId === '';
        if (isNullParent) {
            currentParentId = null;
        } else {
            // Convert to integer, handling both string and number types
            const parsed = parseInt(currentParentId, 10);
            currentParentId = isNaN(parsed) ? null : parsed;
        }

        // Build folder options (excluding current folder and its descendants)
        const isBbbScope = !!(folder.is_bbb_scope || folder.scope === 'bbb');
        const optionsTree = isBbbScope ? bbbFolderTree : folderTree;
        const folderOptions = buildFolderOptions(optionsTree, folderId, currentParentId);

        // Verify parent is in options - if not, add it manually
        if (currentParentId !== null) {
            const parentInOptions = folderOptions.some(opt => {
                const optId = parseInt(opt.value, 10);
                return optId === currentParentId;
            });

            if (!parentInOptions) {
                // Parent folder not found in options, find it in tree and add it
                const parentFolder = findFolderInTree(optionsTree, currentParentId);
                if (parentFolder) {
                    // Insert parent folder option at the beginning (after Root)
                    const rootIndex = folderOptions.findIndex(opt => opt.value === '0');
                    const insertIndex = rootIndex >= 0 ? rootIndex + 1 : 0;
                    folderOptions.splice(insertIndex, 0, {
                        value: currentParentId.toString(),
                        label: parentFolder.name,
                        selected: true
                    });
                }
            } else {
                // Parent is in options, ensure it's selected
                folderOptions.forEach(opt => {
                    const optId = parseInt(opt.value, 10);
                    if (optId === currentParentId) {
                        opt.selected = true;
                    } else if (opt.value !== '0') {
                        opt.selected = false;
                    }
                });
            }
        }

        // Render modal body with name input and folder selector
        const nameInput = await renderFolderInput(strings[1], folder.name);
        const folderSelect = await Templates.render('mod_videolesson/assign_folder_modal', {
            options: folderOptions
        });

        const body = nameInput + folderSelect;

        const modal = await ModalSaveCancel.create({
            title: strings[0],
            body: body,
            buttons: {
                cancel: strings[3],
                save: strings[4],
            },
            removeOnClose: true,
        });

        modal.getRoot().on(ModalEvents.save, async () => {
            const name = modal.getRoot().find(`input[name="${folderInputName}"]`).val();
            if (name && name.trim()) {
                // Get selected parent folder
                const selectedParent = modal.getRoot().find('#videolesson-assign-folder-select').val();
                let newParentId = null;
                if (selectedParent && selectedParent !== '0' && selectedParent !== 'null') {
                    newParentId = parseInt(selectedParent, 10);
                    if (isNaN(newParentId)) {
                        newParentId = null;
                    }
                }

                await updateFolder(folderId, name.trim(), newParentId);
                modal.destroy();
            }
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Show delete folder dialog
 *
 * @param {number} folderId Folder ID to delete
 */
const showDeleteFolderDialog = async (folderId) => {
    const strings = await Promise.all([
        getString('folder:delete', 'mod_videolesson'),
        getString('folder:delete_confirm', 'mod_videolesson'),
        getString('folder:delete_option_move', 'mod_videolesson'),
        getString('folder:delete_option_remove', 'mod_videolesson'),
        getString('cancel'),
        getString('delete'),
    ]);

    try {
        const body = `
            <p>${strings[1]}</p>
            <div class="form-check">
                <input class="form-check-input" type="radio"
                    name="videolesson-delete-mode" id="videolesson-delete-move" value="move" checked>
                <label class="form-check-label" for="videolesson-delete-move">${strings[2]}</label>
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="radio"
                    name="videolesson-delete-mode" id="videolesson-delete-remove" value="delete">
                <label class="form-check-label" for="videolesson-delete-remove">${strings[3]}</label>
            </div>`;

        const modal = await ModalSaveCancel.create({
            title: strings[0],
            body: body,
            buttons: {
                cancel: strings[4],
                save: strings[5],
            },
            removeOnClose: true,
        });

        modal.getRoot().on(ModalEvents.save, async () => {
            const mode = modal.getRoot().find('input[name="videolesson-delete-mode"]:checked').val();
            await deleteFolder(folderId, mode === 'move');
            modal.destroy();
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Create folder
 *
 * @param {string} name Folder name
 * @param {number|null} parentId Parent folder ID
 */
const createFolder = async (name, parentId) => {
    try {
        let parentArg = parentId;
        if (parentArg === null || parentArg === undefined || parentArg === '' || parentArg === 0 || parentArg === '0') {
            parentArg = null;
        } else if (parentArg === 'bbb') {
            parentArg = 'bbb';
        } else {
            parentArg = parseInt(parentArg, 10);
        }

        const response = await Ajax.call([{
            methodname: 'mod_videolesson_create_folder',
            args: {
                name: name,
                parentid: parentArg
            }
        }])[0];

        if (response.success) {
            await loadFolderTree();
            Toast.add(await getString('folder:create_success', 'mod_videolesson'));
        } else {
            Toast.add(response.error || await getString('folder:create_error', 'mod_videolesson'), {type: 'error'});
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Update folder
 *
 * @param {number} folderId Folder ID to update
 * @param {string} name New folder name
 * @param {number|null} parentId New parent folder ID
 */
const updateFolder = async (folderId, name, parentId = null) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_videolesson_update_folder',
            args: {
                folderid: folderId,
                name: name,
                parentid: parentId
            }
        }])[0];

        if (response.success) {
            await loadFolderTree();
            Toast.add(await getString('folder:update_success', 'mod_videolesson'));
        } else {
            Toast.add(response.error || await getString('folder:update_error', 'mod_videolesson'), {type: 'error'});
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Delete folder
 *
 * @param {number} folderId Folder ID to delete
 * @param {boolean} moveVideos Whether to move videos to the root folder
 */
const deleteFolder = async (folderId, moveVideos = false) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_videolesson_delete_folder',
            args: {
                folderid: folderId,
                movevideos: moveVideos
            }
        }])[0];

        if (response.success) {
            await loadFolderTree();
            Toast.add(await getString('folder:delete_success', 'mod_videolesson'));
            document.dispatchEvent(new CustomEvent('videolesson:folder:selected', {
                detail: {folderId: 0}
            }));
        } else {
            Toast.add(response.error || await getString('folder:delete_error', 'mod_videolesson'), {type: 'error'});
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Move video to folder
 *
 * @param {number} videoId Video ID to move
 * @param {number|null} folderId Target folder ID
 */
const moveVideo = async (videoId, folderId) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_videolesson_move_video',
            args: {
                videolessonid: videoId,
                folderid: folderId
            }
        }])[0];

        if (response.success) {
            const msg = await getString('folder:move_video_success', 'mod_videolesson');
            Toast.add(msg);

            // Reload folder tree counts and refresh current folder listing.
            await loadFolderTree();
            const event = new CustomEvent('videolesson:folder:selected', {
                detail: {folderId: selectedFolderId}
            });
            document.dispatchEvent(event);
        } else {
            Toast.add(response.error || await getString('folder:move_video_error', 'mod_videolesson'), {type: 'error'});
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Move BBB recording within BBB folders.
 *
 * @param {string} recordingId
 * @param {number|string|null} folderId Target folder id or 'bbb'
 */
const moveBbbRecording = async (recordingId, folderId) => {
    try {
        const target = (folderId === null || folderId === 0 || folderId === '0') ? 'bbb' : String(folderId);
        const response = await Ajax.call([{
            methodname: 'mod_videolesson_move_bbb_recording',
            args: {
                recordingid: recordingId,
                folderid: target
            }
        }])[0];

        if (response.success) {
            const msg = await getString('folder:move_video_success', 'mod_videolesson');
            Toast.add(msg);
            await loadFolderTree();
            const event = new CustomEvent('videolesson:folder:selected', {
                detail: {folderId: selectedFolderId}
            });
            document.dispatchEvent(event);
        } else {
            Toast.add(response.error || await getString('folder:move_video_error', 'mod_videolesson'), {type: 'error'});
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Get selected folder ID
 */
export const getSelectedFolderId = () => {
    return selectedFolderId;
};

export const getFolderTreeData = () => {
    return folderTree.slice();
};

export const getBbbFolderTreeData = () => {
    return bbbFolderTree.slice();
};

export const ensureFolderTreeData = async () => {
    if (!folderTree.length && !bbbFolderTree.length) {
        await loadFolderTree();
    }
    return folderTree.slice();
};

export const ensureBbbFolderTreeData = async () => {
    if (!folderTree.length && !bbbFolderTree.length) {
        await loadFolderTree();
    }
    return bbbFolderTree.slice();
};

