/**
 * Calendar component.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 * @author Paco Pompeani <paco.pompeani@etu.utc.fr>
 *
 * @copyright Copyright (c) 2019, SiMDE-UTC
 * @license GNU GPL-3.0
 */

import React from 'react';
import { connect } from 'react-redux';
import BigCalendar from 'react-big-calendar';
import moment from 'moment';

import actions from '../redux/actions';

const localizer = BigCalendar.momentLocalizer(moment);

const messages = {
	allDay: 'Journée',
	previous: 'Précédent',
	next: 'Suivant',
	today: "Aujourd'hui",
	month: 'Mois',
	week: 'Semaine',
	day: 'Jour',
	agenda: 'Agenda',
	date: 'Date',
	time: 'Heure',
	event: 'Événement', // Or anything you want
	showMore: total => `+ ${total} événement(s) supplémentaire(s)`,
};

@connect()
class GlobalCalendar extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			events: [],
			date: new Date(),
			duration: GlobalCalendar.getDefaultView(),
		};

		this.loadEvents();
	}

	static getDefaultView() {
		return window.innerWidth > 500 ? 'week' : 'day';
	}

	static getEventProps() {
		return {
			style: {
				border: 'none',
				fontSize: '12px',
			},
		};
	}

	onNavigate(date) {
		this.setState(
			state => ({ ...state, date }),
			() => this.loadEvents()
		);
	}

	getEvents() {
		const { events } = this.state;
		const generatedEvents = [];
		events.forEach(({ id, name, begin_at, end_at, owned_by }) => {
			generatedEvents.push({
				id,
				title: owned_by ? `${owned_by.shortname} - ${name}` : name,
				start: new Date(begin_at),
				end: new Date(end_at),
			});
		});
		return generatedEvents;
	}

	loadEvents() {
		const { dispatch } = this.props;
		const { duration, date } = this.state;
		let param;
		if (duration === 'agenda') {
			param = {
				month: moment()
					.startOf('day')
					.format('YYYY-MM-DD'),
			};
		} else {
			// duration in [ 'day', 'week', 'month' ]
			param = {
				[duration]: moment(date)
					.startOf(duration)
					.format('YYYY-MM-DD'),
			};
		}

		const action = actions.events.all(param);

		dispatch(action);
		action.payload
			.then(({ data }) => {
				this.setState(prevState => {
					prevState.events = data;
					return prevState;
				});
			})
			.catch(() => {
				this.setState(prevState => {
					prevState.events = [];
					return prevState;
				});
			});
	}

	render() {
		const { events } = this.state;
		const fetching = events === [];

		return (
			<div className="container Calendar mt-3">
				<div style={{ height: '700px' }}>
					<BigCalendar
						localizer={localizer}
						defaultView={GlobalCalendar.getDefaultView()}
						eventPropGetter={GlobalCalendar.getEventProps}
						{...this.props}
						events={this.getEvents()}
						messages={messages}
						onNavigate={date => this.onNavigate(date)}
					/>
				</div>
				<span className={`loader large${fetching ? ' active' : ''}`} />
			</div>
		);
	}
}

export default GlobalCalendar;
