
/**
 * Gutenberg Block for CB-items, based on core/latest-posts
 *
 * What works:
 *  -Custom CB category display
 *
 *
 *  What doesn't work:
 *  - Available until date not toggleable
 *  - Rendering of backend view looks wrong
 *  - Category and author filtering in editor
 *
 *
 */


/**
 * External dependencies
 */
import { includes, pickBy } from 'lodash';

/**
 * WordPress dependencies
 */
import {
    PanelBody,
    Placeholder,
    QueryControls,
    Spinner,
    ToggleControl,
} from '@wordpress/components';
import { __} from '@wordpress/i18n';
import {
    InspectorControls,
    useBlockProps,
} from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticeStore } from '@wordpress/notices';
import { useInstanceId } from '@wordpress/compose';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Internal dependencies
 */

/**
 * Module Constants
 */
const CATEGORIES_LIST_QUERY = {
    per_page: -1,
    context: 'view',
};
const USERS_LIST_QUERY = {
    per_page: -1,
    has_published_posts: [ 'cb_item' ],
    context: 'view',
};

export default function Edit( { attributes, setAttributes } ) {
    const instanceId = useInstanceId( Edit );
    const {
        postsToShow,
        order,
        orderBy,
        categories,
        selectedAuthor,
        displayAvailText,
    } = attributes;
    const {
        latestPosts,
        categoriesList,
        authorList,
    } = useSelect(
        ( select ) => {
            const { getEntityRecords, getUsers } = select( coreStore );
            const catIds =
                categories && categories.length > 0
                    ? categories.map( ( cat ) => cat.id )
                    : [];
            const latestPostsQuery = pickBy(
                {
                    categories: catIds,
                    author: selectedAuthor,
                    order,
                    orderby: orderBy,
                    per_page: postsToShow,
                    _embed: 'wp:featuredmedia',
                },
                ( value ) => typeof value !== 'undefined'
            );

            return {
                latestPosts: getEntityRecords(
                    'postType',
                    'cb_item',
                    latestPostsQuery
                ),
                categoriesList: getEntityRecords(
                    'taxonomy',
                    'cb_items_category',
                    CATEGORIES_LIST_QUERY
                ),
                authorList: getUsers( USERS_LIST_QUERY ),
            };
        },
        [
            postsToShow,
            order,
            orderBy,
            categories,
            selectedAuthor,
        ]
    );

    // If a user clicks a link prevent redirection and show a warning.
    const { createWarningNotice, removeNotice } = useDispatch( noticeStore );
    let noticeId;
    const showRedirectionPreventedNotice = ( event ) => {
        event.preventDefault();
        // Remove previous warning if any, to show one at a time per block.
        removeNotice( noticeId );
        noticeId = `commonsbooking/cb-items/redirection-prevented/${ instanceId }`;
        createWarningNotice( __( 'Links are disabled in the editor.' ), {
            id: noticeId,
            type: 'snackbar',
        } );
    };

    const categorySuggestions =
        categoriesList?.reduce(
            ( accumulator, category ) => ( {
                ...accumulator,
                [ category.name ]: category,
            } ),
            {}
        ) ?? {};
    const selectCategories = ( tokens ) => {
        const hasNoSuggestion = tokens.some(
            ( token ) =>
                typeof token === 'string' && ! categorySuggestions[ token ]
        );
        if ( hasNoSuggestion ) {
            return;
        }
        // Categories that are already will be objects, while new additions will be strings (the name).
        // allCategories normalizes the array so that they are all objects.
        const allCategories = tokens.map( ( token ) => {
            return typeof token === 'string'
                ? categorySuggestions[ token ]
                : token;
        } );
        // We do nothing if the category is not selected
        // from suggestions.
        if ( includes( allCategories, null ) ) {
            return false;
        }
        setAttributes( { categories: allCategories } );
    };

    const hasPosts = !! latestPosts?.length;
    const inspectorControls = (
        <InspectorControls>
            <PanelBody title={ __( 'Post meta settings' ) }>
                <ToggleControl
                    label={ __( 'Display "available until" date' ) }
                    checked={ displayAvailText }
                    onChange={ ( value ) =>
                        setAttributes( { displayAvailText: value } )
                    }
                />
            </PanelBody>

            <PanelBody title={ __( 'Sorting and filtering' ) }>
                <QueryControls
                    { ...{ order, orderBy } }
                    numberOfItems={ postsToShow }
                    onOrderChange={ ( value ) =>
                        setAttributes( { order: value } )
                    }
                    onOrderByChange={ ( value ) =>
                        setAttributes( { orderBy: value } )
                    }
                    onNumberOfItemsChange={ ( value ) =>
                        setAttributes( { postsToShow: value } )
                    }
                    categorySuggestions={ categorySuggestions }
                    onCategoryChange={ selectCategories }
                    selectedCategories={ categories }
                    onAuthorChange={ ( value ) =>
                        setAttributes( {
                            selectedAuthor:
                                '' !== value ? Number( value ) : undefined,
                        } )
                    }
                    authorList={ authorList ?? [] }
                    selectedAuthorId={ selectedAuthor }
                />
            </PanelBody>
        </InspectorControls>
    );

    const blockProps = useBlockProps();

    if ( ! hasPosts ) {
        return (
            <div { ...blockProps }>
                { inspectorControls }
                <Placeholder>
                    { ! Array.isArray( latestPosts ) ? (
                        <Spinner />
                    ) : (
                        __( 'No items found.' )
                    ) }
                </Placeholder>
            </div>
        );
    }

    return (
        <div { ...blockProps}>
            { inspectorControls }
            <ServerSideRender
                block="commonsbooking/cb-items"
                attributes={ {
                    selectedAuthor  : selectedAuthor,
                    postsToShow     : postsToShow,
                    displayAvailText: displayAvailText,
                    order           : order,
                    orderBy         : orderBy,
                } }
            />
        </div>
    );
}
