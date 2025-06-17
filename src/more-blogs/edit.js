import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { store as coreDataStore } from '@wordpress/core-data';
import { Spinner, Button } from '@wordpress/components';

export default function Edit() {
	const blockProps = useBlockProps();
	const perPage = 3;
	const [ currentPage, setCurrentPage ] = useState( 1 );

	const { posts, totalItems, totalPages, isLoading } = useSelect(
		( select ) => {
			const query = {
				per_page: perPage,
				status: 'publish',
				offset: ((currentPage - 1) * perPage) + 1,
				show_in_rest: true,
				_embed: true,
			};

			const records = select( coreDataStore ).getEntityRecords( 'postType', 'post', query );
			const totalItems = select( coreDataStore ).getEntityRecordsTotalItems( 'postType', 'post', query );
			const totalPages = totalItems ? Math.ceil( ( totalItems - 1 ) / perPage ) : 1;

			const isLoading = ! select( coreDataStore ).hasFinishedResolution(
				'getEntityRecords',
				[ 'postType', 'post', query ]
			);

			return {
				posts: records,
				totalItems,
				totalPages,
				isLoading,
			};
		},
		[ currentPage ]
	);

	if ( isLoading ) {
		return <Spinner />;
	}

	if ( ! posts || posts.length === 0 ) {
		return <p { ...blockProps }>No posts found.</p>;
	}

	return (
		<div { ...blockProps }>
			<div className="more-posts">
				<h2 className="grid-title">Discover More</h2>
				<div className="post-grid">
					{ posts.map( ( post ) => {
						const title = post.title?.rendered || '(No title)';
						const date = new Date(post.date).toLocaleDateString('en-US', {
							year: 'numeric',
							month: 'long',
							day: 'numeric',
						});
						const readTime = post.meta?.read_time || null;
						const roundedReadTime = readTime !== undefined && readTime !== null
								? Math.round(parseFloat(readTime))
								: null;
						const link = post.link;
						const excerpt = ( post.excerpt?.rendered || '' )
							.replace( /\[&hellip;\]/g, '' )
							.replace( /<[^>]+>/g, '' )
							.split(/\s+/)
							.slice(0, 20)
							.join(' ') + '…';

						const categories = post._embedded?.['wp:term']?.[0]?.map( t => t.name ) || [];
						const thumb = post._embedded?.['wp:featuredmedia']?.[0]?.source_url || null;

						return (
							<article className="post-card" key={ post.id }>
								<a href={ link } target="_blank" rel="noopener noreferrer">
									{ thumb && (
										<div className="post-thumb">
											<img className="featured-img" src={ thumb } alt={ title } />
										</div>
									) }
									<div className="post-body">
										<div className="categories">
											<ul className="category-badges">
												{ categories.map( ( cat, i ) => (
													<li className="category-badge">{ cat }</li>
												) ) }
											</ul>
										</div>
										<h2 className="title">{ title }</h2>
										<div className="date">{ date }</div>
										{ roundedReadTime !== null && (
											<div className="read-time">
												{roundedReadTime < 1 ? '< 1' : roundedReadTime} min read
											</div>
										)}
										<div className="excerpt">{ excerpt }</div>
									</div>
								</a>
							</article>
						);
					} ) }
				</div>

				<ul className="page-numbers">
					<li>
						<Button
							className={ `prev page-numbers ${ currentPage === 1 ? 'hidden' : '' }` }
							onClick={ () => setCurrentPage( (prev) => Math.max( 1, prev - 1 ) ) }
						>
							‹
						</Button>
					</li>
					{ Array.from( { length: totalPages }, ( _, i ) => {
						const page = i + 1;
						return (
							<li>
								<Button
									className={ `page-numbers ${ currentPage === page ? 'current' : '' }` }
									disabled={ currentPage === page }
									onClick={ () => setCurrentPage( page ) }
								>
									{ page }
								</Button>
							</li>
						);
					} ) }
					<li>
						<Button
							className={`next page-numbers ${ currentPage === totalPages ? 'hidden' : '' }`}
							onClick={ () => setCurrentPage( (prev) => prev + 1 ) }
						>
							›
						</Button>
					</li>
				</ul>
			</div>
		</div>
	);
}