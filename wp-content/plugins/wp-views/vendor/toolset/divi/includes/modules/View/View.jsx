// External Dependencies
import React, { Component } from 'react';

// Internal Dependencies
import './style.css';

class View extends Component {

  static slug = 'toolset_divi_view';

  constructor(props) {
    super(props);
    this.state = {
      error: null,
      isLoaded: false,
      slug: '',
      data: ''
    };
  }

  componentDidUpdate( prevProps, prevState ) {
    if ( ! this.state.isLoaded || ! this.state.slug || ! this.state.data ) {
      return;
    }

    if ( prevState.slug !== this.state.slug ) {
      this.maybeSendStyleToHead();
    }
  }

  fetchPreview() {
    const slug = this.props.toolset_view;
    const args = new FormData();

    args.append( 'action', 'toolset_divi_render_preview' );
    args.append( 'slug', slug );

    fetch(window.et_pb_custom.ajaxurl, {
      method: 'post',
      body: args
    }).then(
      res => res.text()
    ).then(
      result => {
        this.setState({
          isLoaded: true,
          slug: slug,
          data: result
        });
      },
      error => {
        this.setState({
          isLoaded: true,
          error
        });
      }
    );
  }

	maybeSendStyleToHead() {
  		if ( !! window.toolsetCommonEs.styleToHead ) {
			window.toolsetCommonEs.styleToHead();
		}
	}

  render() {
    const { error, isLoaded, slug, data } = this.state;

    if ( this.props.toolset_view !== slug ) {
      this.fetchPreview();
    }

    if (error) {
      return <div>{error.message}</div>;
    } else if (!isLoaded) {
      return <div>...</div>;
    } else {
      return <div dangerouslySetInnerHTML={{__html: data}} />;
    }
  }
}

export default View;
